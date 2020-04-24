<?php
/**
 * Message
 *
 * @copyright Copyright © 2017 Staempfli AG. All rights reserved.
 * @author    juan.alonso@staempfli.com
 */

namespace Staempfli\MailCatcher\Mail;

use Magento\Framework\Mail\Address;
use Magento\Framework\Mail\AddressFactory;
use Magento\Framework\Mail\MimeMessageInterface;
use Magento\Framework\Mail\MimeMessageInterfaceFactory;
use Staempfli\MailCatcher\Config\CatcherConfig;
use Staempfli\MailCatcher\Repository\MailCatcherRepository;

class Message extends \Magento\Framework\Mail\EmailMessage
{
    /**
     * @var CatcherConfig
     */
    private $catcherConfig;
    /**
     * @var MailCatcherRepository
     */
    private $mailCatcherRepository;

    private $recipients = [];

    public function __construct(
        CatcherConfig $catcherConfig,
        MailCatcherRepository $mailCatcherRepository,
        MimeMessageInterface $body,
        array $to,
        MimeMessageInterfaceFactory $mimeMessageFactory,
        AddressFactory $addressFactory,
        ?array $from = null,
        ?array $cc = null,
        ?array $bcc = null,
        ?array $replyTo = null,
        ?Address $sender = null,
        ?string $subject = '',
        ?string $encoding = 'utf-8'
    ) {
        $this->catcherConfig = $catcherConfig;
        $this->mailCatcherRepository = $mailCatcherRepository;
        parent::__construct($body, $to, $mimeMessageFactory, $addressFactory, $from, $cc, $bcc, $replyTo, $sender, $subject, $encoding);
    }

    /**
     * {@inheritdoc}
     */
    public function addTo($address, $name = '')
    {
        $redirectAddress = $this->getRedirectRecipient($address);
        if ($redirectAddress) {
            $address = $redirectAddress;
        }

        $this->recipients[] = $address;
        parent::addTo($address);
    }

    /**
     * {@inheritdoc}
     */
    public function addCc($address, $name = '')
    {
        $redirectAddress = $this->getRedirectRecipient($address);
        if ($redirectAddress) {
            $address = $redirectAddress;
        }
        return parent::addCc($address);
    }

    /**
     * {@inheritdoc}
     */
    public function addBcc($address)
    {
        $redirectAddress = $this->getRedirectRecipient($address);
        if ($redirectAddress) {
            $address = $redirectAddress;
        }
        return parent::addBcc($address);
    }

    public function getRecipients()
    {
        return $this->recipients;
    }

    /**
     * @param $address
     * @return bool|string|array
     */
    private function getRedirectRecipient($address)
    {
        if ($this->catcherConfig->isCatcherEnabled()) {
            $redirectRecipient = $this->catcherConfig->redirectRecipient();
            if ($redirectRecipient) {
                return $this->getAddressWithRedirectRecipient($address, $redirectRecipient);
            }
        }
        return false;
    }

    /**
     * @param $address
     * @param $redirectRecipient
     * @return array|string
     */
    private function getAddressWithRedirectRecipient($address, $redirectRecipient)
    {
        if (is_array($address)) {
            foreach ($address as &$email) {
                if (!$this->mailCatcherRepository->isRecipientWhiteListed($email)) {
                    $email = $redirectRecipient;
                }
            }
        }
        if (is_string($address)) {
            if (!$this->mailCatcherRepository->isRecipientWhiteListed($address)) {
                $address = $redirectRecipient;
            }
        }
        return $address;
    }
}
