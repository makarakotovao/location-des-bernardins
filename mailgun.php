<?php
require 'vendor/autoload.php';
use Mailgun\Mailgun;
$mailgun = new Mailgun('api_key', new \Http\Adapter\Guzzle6\Client());

# Instantiate the client.
$mgClient = new Mailgun('key-79d7005fc7dc20c1c8d92158a79a49b1');
$domain = "mg.sinod.fr";

# Make the call to the client.
function mg_mail($to, $subject, $message, $from = '', $bcc = '') {
  global $domain, $mgClient;

  if ($from == '') {
    $from = 'Collège des Bernardins <internet@collegedesbernardins.fr>';
  }
  $result = $mgClient->sendMessage($domain, array(
    'from'    => $from,
    'to'      => $to,
    'bcc'      => $bcc,
    'subject' => $subject,
    'text'    => strip_tags($message),
    'html'    => $message,
  ));

  return $result;
}
?>
