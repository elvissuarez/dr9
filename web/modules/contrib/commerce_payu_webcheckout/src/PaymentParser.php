<?php

namespace Drupal\commerce_payu_webcheckout;

use Drupal\commerce_payu_webcheckout\Plugin\PayuItemInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A payment result parser.
 */
class PaymentParser implements PaymentParserInterface {

  use StringTranslationTrait;

  const SUCCESSFUL_PAYMENT = 4;

  protected $isSuccessful;

  protected $reason;

  protected $remoteState;

  protected $remoteId;

  protected $currentRequest;

  protected $method;

  protected $payuItemManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(RequestStack $stack, PluginManagerInterface $payu_item_manager) {
    $this->payuItemManager = $payu_item_manager;
    $this->currentRequest = $stack->getCurrentRequest();
    $this->method = $this->currentRequest->getMethod();
  }

  /**
   * {@inheritdoc}
   */
  public function isSuccessful() {
    $code = $this->currentRequest->get('state_pol');
    return (int) $code == self::SUCCESSFUL_PAYMENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    $messages = [
      'APPROVED' => $this->t('Transaction approved'),
      'PAYMENT_NETWORK_REJECTED' => $this->t('Transaction rejected by payment network'),
      'ENTITY_DECLINED' => $this->t('Transaction has been declined by the bank'),
      'INSUFFICIENT_FUNDS' => $this->t('Insufficient funds'),
      'INVALID_CARD' => $this->t('Invalid Card'),
      'CONTACT_THE_ENTITY' => $this->t('Please contact your financial entity'),
      'BANK_ACCOUNT_ACTIVATION_ERROR' => $this->t('Automatic debit not allowed'),
      'BANK_ACCOUNT_NOT_AUTHORIZED_FOR_AUTOMATIC_DEBIT' => $this->t('Automatic debit not allowed'),
      'INVALID_AGENCY_BANK_ACCOUNT' => $this->t('Automatic debit not allowed'),
      'INVALID_BANK_ACCOUNT' => $this->t('Automatic debit not allowed'),
      'INVALID_BANK' => $this->t('Automatic debit not allowed'),
      'EXPIRED_CARD' => $this->t('Expired card'),
      'RESTRICTED_CARD' => $this->t('Restricted card'),
      'INVALID_EXPIRATION_DATE_OR_SECURITY_CODE' => $this->t('Date of expiration or security code is invalid'),
      'REPEAT_TRANSACTION' => $this->t('Retry the transaction'),
      'INVALID_TRANSACTION' => $this->t('Transaction invalid'),
      'EXCEEDED_AMOUNT' => $this->t('Value exceeds maximum allowed by this entity'),
      'ABANDONED_TRANSACTION' => $this->t('Transaction abandoned by the payer'),
      'CREDIT_CARD_NOT_AUTHORIZED_FOR_INTERNET_TRANSACTIONS' => $this->t('Card is not authorized for internet purchases'),
      'ANTIFRAUD_REJECTED' => $this->t('Transaction has been rejected by the anti-fraud module'),
      'DIGITAL_CERTIFICATE_NOT_FOUND' => $this->t('Digital certificate not found'),
      'BANK_UNREACHABLE' => $this->t('Error trying to communicate with the bank'),
      'ENTITY_MESSAGING_ERROR' => $this->t('Error communicating with the financial institution'),
      'NOT_ACCEPTED_TRANSACTION' => $this->t('Transaction not permitted to cardholder'),
      'INTERNAL_PAYMENT_PROVIDER_ERROR' => $this->t('Internal error'),
      'INACTIVE_PAYMENT_PROVIDER' => $this->t('Internal error'),
      'ERROR' => $this->t('Internal error'),
      'ERROR_CONVERTING_TRANSACTION_AMOUNTS' => $this->t('Internal error'),
      'BANK_ACCOUNT_ACTIVATION_ERROR' => $this->t('Internal error'),
      'FIX_NOT_REQUIRED' => $this->t('Internal error'),
      'AUTOMATICALLY_FIXED_AND_SUCCESS_REVERSAL' => $this->t('Internal error'),
      'AUTOMATICALLY_FIXED_AND_UNSUCCESS_REVERSAL' => $this->t('Internal error'),
      'AUTOMATIC_FIXED_NOT_SUPPORTED' => $this->t('Internal error'),
      'NOT_FIXED_FOR_ERROR_STATE' => $this->t('Internal error'),
      'ERROR_FIXING_AND_REVERSING' => $this->t('Internal error'),
      'ERROR_FIXING_INCOMPLETE_DATA' => $this->t('Internal error'),
      'PAYMENT_NETWORK_BAD_RESPONSE' => $this->t('Internal error'),
      'PAYMENT_NETWORK_NO_CONNECTION' => $this->t('Unable to communicate with the financial institution'),
      'PAYMENT_NETWORK_NO_RESPONSE' => $this->t('No response from the financial institution'),
      'EXPIRED_TRANSACTION' => $this->t('Transaction expired'),
      'PENDING_TRANSACTION_REVIEW' => $this->t('Transaction is pending approval'),
      'PENDING_TRANSACTION_CONFIRMATION' => $this->t('Receipt of payment generated. Pending payment'),
      'PENDING_TRANSACTION_TRANSMISSION' => $this->t('Not permitted transaction'),
      'PENDING_PAYMENT_IN_ENTITY' => $this->t('Receipt of payment generated. Pending payment'),
      'PENDING_PAYMENT_IN_BANK' => $this->t('Receipt of payment generated. Pending payment'),
      'PENDING_SENT_TO_FINANCIAL_ENTITY' => $this->t('Pending notice sent to financial entity'),
      'PENDING_AWAITING_PSE_CONFIRMATION' => $this->t('Pending confirmation from PSE'),
      'PENDING_NOTIFYING_ENTITY' => $this->t('Receipt of payment generated. Pending payment'),
    ];
    return isset($messages[$this->getRemoteState()]) ? $messages[$this->getRemoteState()] : $this->t('Unknown error');
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteState() {
    return $this->currentRequest->get('response_message_pol');
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteId() {
    return $this->currentRequest->get('reference_pol');
  }

  /**
   * {@inheritdoc}
   */
  public function getItems() {
    $data = [];
    $definitions = $this->payuItemManager->getDefinitions();
    foreach ($definitions as $definition) {
      $plugin = $this->payuItemManager->createInstance($definition['id']);
      if ($plugin instanceof PayuItemInterface) {
        $consumed_data = $plugin->consumeValue($this->currentRequest);
        if ($consumed_data) {
          $data[$plugin->getId()] = $consumed_data;
        }
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    switch ($this->getRemoteState()) {
      case 'APPROVED':
        $state = 'completed';
        break;

      case 'PENDING_TRANSACTION_REVIEW':
      case 'PENDING_TRANSACTION_CONFIRMATION':
      case 'PENDING_TRANSACTION_TRANSMISSION':
      case 'PENDING_PAYMENT_IN_ENTITY':
      case 'PENDING_PAYMENT_IN_BANK':
      case 'PENDING_SENT_TO_FINANCIAL_ENTITY':
      case 'PENDING_AWAITING_PSE_CONFIRMATION':
      case 'PENDING_NOTIFYING_ENTITY':
        $state = 'pending';
        break;

      default:
        $state = 'voided';
    }
    return $state;
  }

}
