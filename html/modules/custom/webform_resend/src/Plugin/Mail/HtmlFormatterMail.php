<?php

namespace Drupal\webform_resend\Plugin\Mail;

use Drupal\Core\Mail\Plugin\Mail\PhpMail;
use Drupal\Core\Mail\MailFormatHelper;


/**
 * Provides a 'HtmlFormatterMail' mail plugin to extend the default Drupal mail backend to support HTML email.
 *
 * @Mail(
 *  id = "html_formatter_mail",
 *  label = @Translation("Html formatter mail"),
 *  description = @Translation("Sends the message as HTML, using PHP's native mail() function.")
 * )
 */
class HtmlFormatterMail extends PhpMail {

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
    $message['body'] = implode("\n\n", $message['body']);
    $message['body'] = MailFormatHelper::wrapMail($message['body']);

    return $message;
  }
}
