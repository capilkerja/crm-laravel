<?php

namespace App\Services;

use Google_Service_Gmail_Message;
use Google_Client;
use Google_Service_Gmail;
use App\Models\Email;

class GmailService
{
    protected $client;
    protected $service;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setApplicationName(config('services.gmail.application_name'));
        $this->client->setScopes(Google_Service_Gmail::GMAIL_MODIFY);
        $this->client->setAuthConfig(config('services.gmail.credentials_path'));
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');

        $this->service = new Google_Service_Gmail($this->client);
    }

    public function getUnreadMessages()
    {
        $user = 'me';
        $optParams = [
            'q' => 'is:unread',
            'maxResults' => 10,
        ];

        $messages = $this->service->users_messages->listUsersMessages($user, $optParams);

        return $messages->getMessages();
    }

    public function getMessage($messageId)
    {
        $user = 'me';
        $message = $this->service->users_messages->get($user, $messageId);
        $this->trackEmail($message);
        return $message;
    }

    public function sendReply($messageId, $body)
    {
        $user = 'me';
        $reply = $this->createReplyMessage($messageId, $body);
        $sentMessage = $this->service->users_messages->send($user, $reply);
        $this->trackEmail($sentMessage, true);
        return $sentMessage;
    }

    protected function trackEmail($message, $isSent = false)
    {
        $headers = $this->parseHeaders($message->getPayload()->getHeaders());

        Email::create([
            'message_id' => $message->getId(),
            'sender' => $headers['From'] ?? '',
            'recipient' => $headers['To'] ?? '',
            'subject' => $headers['Subject'] ?? '',
            'content' => $this->getEmailContent($message),
            'timestamp' => $headers['Date'] ?? now(),
            'is_sent' => $isSent,
        ]);
    }

    protected function parseHeaders($headers)
    {
        $parsedHeaders = [];
        foreach ($headers as $header) {
            $parsedHeaders[$header->getName()] = $header->getValue();
        }
        return $parsedHeaders;
    }

    protected function getEmailContent($message)
    {
        $payload = $message->getPayload();
        if (!$payload) {
            return '';
        }

        $parts = $payload->getParts();
        $body = $payload->getBody();

        if ($body && $body->getData()) {
            return base64_decode(strtr($body->getData(), '-_', '+/'));
        }

        if ($parts) {
            foreach ($parts as $part) {
                if ($part['mimeType'] === 'text/plain') {
                    $data = $part['body']['data'];
                    return base64_decode(strtr($data, '-_', '+/'));
                }
            }
        }

        return '';
    }

    protected function createReplyMessage($originalMessageId, $replyBody)
    {
        $originalMessage = $this->service->users_messages->get('me', $originalMessageId);
        $headers = $this->parseHeaders($originalMessage->getPayload()->getHeaders());

        $replyMessage = new Google_Service_Gmail_Message();
        $rawMessageString = "From: me\r\n";
        $rawMessageString .= "To: {$headers['From']}\r\n";
        $rawMessageString .= 'Subject: Re: ' . ($headers['Subject'] ?? '') . "\r\n";
        $rawMessageString .= "Content-Type: text/plain; charset=utf-8\r\n";
        $rawMessageString .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $rawMessageString .= base64_encode($replyBody);

        $replyMessage->setRaw(base64_encode($rawMessageString));
        $replyMessage->setThreadId($originalMessage->getThreadId());

        return $replyMessage;
    }
}