<?php

namespace spec\integration\Alphagov\Notifications;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use Alphagov\Notifications\Authentication\JWTAuthenticationInterface;
use Alphagov\Notifications\Client;
use Alphagov\Notifications\Exception\UnexpectedValueException;
use Alphagov\Notifications\Exception\ApiException;

use GuzzleHttp\Psr7\Uri;
use Http\Client\HttpClient as HttpClientInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * Integration Tests for the PHP Notify Client.
 *
 *
 * Class ClientSpec
 * @package spec\Alphagov\Notifications
 */
class ClientSpec extends ObjectBehavior
{
    private static $notificationId;

    function let(){

      $this->beConstructedWith([
            'baseUrl'       => getenv('NOTIFY_API_URL'),
            'apiKey'        => getenv('API_KEY'),
            'httpClient'    => new \Http\Adapter\Guzzle6\Client
        ]);

    }

    function it_is_initializable(){
        $this->shouldHaveType('Alphagov\Notifications\Client');
    }

    function it_receives_the_expected_response_when_sending_an_email_notification(){

        $response = $this->sendEmail( getenv('FUNCTIONAL_TEST_EMAIL'), getenv('EMAIL_TEMPLATE_ID'), [
            "name" => "Foo"
        ]);

        $response->shouldBeArray();
        $response->shouldHaveKey( 'id' );
        $response['id']->shouldBeString();

        $response->shouldHaveKey( 'reference' );

        $response->shouldHaveKey( 'content' );
        $response['content']->shouldBeArray();
        $response['content']->shouldHaveKey( 'from_email' );
        $response['content']['from_email']->shouldBeString();
        $response['content']->shouldHaveKey( 'body' );
        $response['content']['body']->shouldBeString();
        $response['content']['body']->shouldBe("Hello Foo\r\n\r\nFunctional test help make our world a better place");
        $response['content']->shouldHaveKey( 'subject' );
        $response['content']['subject']->shouldBeString();
        $response['content']['subject']->shouldBe("Functional Tests are good");

        $response->shouldHaveKey( 'template' );
        $response['template']->shouldBeArray();
        $response['template']->shouldHaveKey( 'id' );
        $response['template']['id']->shouldBeString();
        $response['template']->shouldHaveKey( 'version' );
        $response['template']['version']->shouldBeInteger();
        $response['template']->shouldHaveKey( 'uri' );

        $response->shouldHaveKey( 'uri' );
        $response['uri']->shouldBeString();

        self::$notificationId = $response['id']->getWrappedObject();

    }

    function it_receives_the_expected_response_when_sending_an_email_notification_with_vaild_emailReplyToId(){

        $response = $this->sendEmail(
          getenv('FUNCTIONAL_TEST_EMAIL'),
          getenv('EMAIL_TEMPLATE_ID'),
          [ "name" => "Foo" ],
          '',
          getenv('EMAIL_REPLY_TO_ID')
        );

        $response->shouldBeArray();
        $response->shouldHaveKey( 'id' );
        $response['id']->shouldBeString();

        $response->shouldHaveKey( 'reference' );

        $response->shouldHaveKey( 'content' );
        $response['content']->shouldBeArray();
        $response['content']->shouldHaveKey( 'from_email' );
        $response['content']['from_email']->shouldBeString();
        $response['content']->shouldHaveKey( 'body' );
        $response['content']['body']->shouldBeString();
        $response['content']['body']->shouldBe("Hello Foo\r\n\r\nFunctional test help make our world a better place");
        $response['content']->shouldHaveKey( 'subject' );
        $response['content']['subject']->shouldBeString();
        $response['content']['subject']->shouldBe("Functional Tests are good");

        $response->shouldHaveKey( 'template' );
        $response['template']->shouldBeArray();
        $response['template']->shouldHaveKey( 'id' );
        $response['template']['id']->shouldBeString();
        $response['template']->shouldHaveKey( 'version' );
        $response['template']['version']->shouldBeInteger();
        $response['template']->shouldHaveKey( 'uri' );

        $response->shouldHaveKey( 'uri' );
        $response['uri']->shouldBeString();

        self::$notificationId = $response['id']->getWrappedObject();
    }

    function it_receives_the_expected_response_when_sending_an_email_notification_with_invaild_emailReplyToId(){

      $this->shouldThrow('Alphagov\Notifications\Exception\ApiException')->duringSendEmail(
        getenv('FUNCTIONAL_TEST_EMAIL'),
        getenv('EMAIL_TEMPLATE_ID'),
        [ "name" => "Foo" ],
        '',
        'invlaid_uuid'
      );
    }

    function it_receives_the_expected_response_when_looking_up_an_email_notification() {

      // Requires the 'it_receives_the_expected_response_when_sending_an_email_notification' test to have completed successfully
      if(is_null(self::$notificationId)) {
          throw new UnexpectedValueException('Notification ID not set');
      }

      $notificationId = self::$notificationId;

      // Retrieve email notification by id and verify contents
      $response = $this->getNotification($notificationId);
      $response->shouldBeArray();
      $response->shouldHaveKey( 'id' );
      $response['id']->shouldBeString();

      $response->shouldHaveKey( 'body' );
      $response['body']->shouldBeString();
      $response['body']->shouldBe("Hello Foo\r\n\r\nFunctional test help make our world a better place");

      $response->shouldHaveKey( 'subject' );
      $response->shouldHaveKey( 'reference' );
      $response->shouldHaveKey( 'email_address' );
      $response['email_address']->shouldBeString();
      $response->shouldHaveKey( 'phone_number' );
      $response->shouldHaveKey( 'line_1' );
      $response->shouldHaveKey( 'line_2' );
      $response->shouldHaveKey( 'line_3' );
      $response->shouldHaveKey( 'line_4' );
      $response->shouldHaveKey( 'line_5' );
      $response->shouldHaveKey( 'line_6' );
      $response->shouldHaveKey( 'postcode' );
      $response->shouldHaveKey( 'type' );
      $response['type']->shouldBeString();
      $response['type']->shouldBe('email');
      $response->shouldHaveKey( 'status' );
      $response['status']->shouldBeString();

      $response->shouldHaveKey( 'template' );
      $response['template']->shouldBeArray();
      $response['template']->shouldHaveKey( 'id' );
      $response['template']['id']->shouldBeString();
      $response['template']->shouldHaveKey( 'version' );
      $response['template']['version']->shouldBeInteger();
      $response['template']->shouldHaveKey( 'uri' );
      $response['template']['uri']->shouldBeString();

      $response->shouldHaveKey( 'created_at' );
      $response->shouldHaveKey( 'sent_at' );
      $response->shouldHaveKey( 'completed_at' );

      self::$notificationId = $response['id']->getWrappedObject();

    }

    function it_receives_the_expected_response_when_sending_an_sms_notification(){

        $response = $this->sendSms( getenv('FUNCTIONAL_TEST_NUMBER'), getenv('SMS_TEMPLATE_ID'), [
            "name" => "Foo"
        ]);

        $response->shouldBeArray();
        $response->shouldHaveKey( 'id' );
        $response['id']->shouldBeString();

        $response->shouldHaveKey( 'reference' );

        $response->shouldHaveKey( 'content' );
        $response['content']->shouldBeArray();
        $response['content']->shouldHaveKey( 'from_number' );
        $response['content']['from_number']->shouldBeString();
        $response['content']->shouldHaveKey( 'body' );
        $response['content']['body']->shouldBeString();
        $response['content']['body']->shouldBe("Hello Foo\r\n\r\nFunctional Tests make our world a better place");

        $response->shouldHaveKey( 'template' );
        $response['template']->shouldBeArray();
        $response['template']->shouldHaveKey( 'id' );
        $response['template']['id']->shouldBeString();
        $response['template']->shouldHaveKey( 'version' );
        $response['template']['version']->shouldBeInteger();
        $response['template']->shouldHaveKey( 'uri' );

        $response->shouldHaveKey( 'uri' );
        $response['uri']->shouldBeString();

        self::$notificationId = $response['id']->getWrappedObject();

    }

    function it_receives_the_expected_response_when_looking_up_an_sms_notification() {

      // Requires the 'it_receives_the_expected_response_when_sending_an_sms_notification' test to have completed successfully
      if(is_null(self::$notificationId)) {
          throw new UnexpectedValueException('Notification ID not set');
      }

      $notificationId = self::$notificationId;

      // Retrieve sms notification by id and verify contents
      $response = $this->getNotification($notificationId);
      $response->shouldBeArray();
      $response->shouldHaveKey( 'id' );
      $response['id']->shouldBeString();

      $response->shouldHaveKey( 'body' );
      $response['body']->shouldBeString();
      $response['body']->shouldBe("Hello Foo\r\n\r\nFunctional Tests make our world a better place");
      $response->shouldHaveKey( 'subject' );

      $response->shouldHaveKey( 'reference' );
      $response->shouldHaveKey( 'email_address' );
      $response->shouldHaveKey( 'phone_number' );
      $response['phone_number']->shouldBeString();
      $response->shouldHaveKey( 'line_1' );
      $response->shouldHaveKey( 'line_2' );
      $response->shouldHaveKey( 'line_3' );
      $response->shouldHaveKey( 'line_4' );
      $response->shouldHaveKey( 'line_5' );
      $response->shouldHaveKey( 'line_6' );
      $response->shouldHaveKey( 'postcode' );
      $response->shouldHaveKey( 'type' );
      $response['type']->shouldBeString();
      $response['type']->shouldBe('sms');
      $response->shouldHaveKey( 'status' );
      $response['status']->shouldBeString();

      $response->shouldHaveKey( 'template' );
      $response['template']->shouldBeArray();
      $response['template']->shouldHaveKey( 'id' );
      $response['template']['id']->shouldBeString();
      $response['template']->shouldHaveKey( 'version' );
      $response['template']['version']->shouldBeInteger();
      $response['template']->shouldHaveKey( 'uri' );
      $response['template']['uri']->shouldBeString();

      $response->shouldHaveKey( 'created_at' );
      $response->shouldHaveKey( 'sent_at' );
      $response->shouldHaveKey( 'completed_at' );

    }

    function it_receives_the_expected_response_when_looking_up_all_notifications() {

      // Retrieve all notifications and verify each is correct (email & sms)
      $response = $this->listNotifications();

      $response->shouldHaveKey('links');
      $response['links']->shouldBeArray();

      $response->shouldHaveKey('notifications');
      $response['notifications']->shouldBeArray();

      $notifications = $response['notifications'];
      $total_notifications_count = count($notifications->getWrappedObject());

      for( $i = 0; $i < $total_notifications_count; $i++ ) {

          $notification = $notifications[$i];

          $notification->shouldBeArray();
          $notification->shouldHaveKey( 'id' );
          $notification['id']->shouldBeString();

          $notification->shouldHaveKey( 'reference' );
          $notification->shouldHaveKey( 'email_address' );
          $notification->shouldHaveKey( 'phone_number' );
          $notification->shouldHaveKey( 'line_1' );
          $notification->shouldHaveKey( 'line_2' );
          $notification->shouldHaveKey( 'line_3' );
          $notification->shouldHaveKey( 'line_4' );
          $notification->shouldHaveKey( 'line_5' );
          $notification->shouldHaveKey( 'line_6' );
          $notification->shouldHaveKey( 'postcode' );
          $notification->shouldHaveKey( 'status' );
          $notification['status']->shouldBeString();

          $notification->shouldHaveKey( 'template' );
          $notification['template']->shouldBeArray();
          $notification['template']->shouldHaveKey( 'id' );
          $notification['template']['id']->shouldBeString();
          $notification['template']->shouldHaveKey( 'version' );
          $notification['template']['version']->shouldBeInteger();
          $notification['template']->shouldHaveKey( 'uri' );
          $notification['template']['uri']->shouldBeString();

          $notification->shouldHaveKey( 'created_at' );
          $notification->shouldHaveKey( 'sent_at' );
          $notification->shouldHaveKey( 'completed_at' );

          $notification->shouldBeArray();

          $notification->shouldHaveKey( 'type' );
          $notification['type']->shouldBeString();
          $notification['type']->shouldBeString();
          $notification_type = $notification['type']->getWrappedObject();

          if ( $notification_type == "sms" ) {

            $notification['phone_number']->shouldBeString();

          } elseif ( $notification_type == "email") {

            $notification['email_address']->shouldBeString();

          }
      }

    }

    function it_receives_the_expected_response_when_looking_up_an_email_template() {
      $templateId = getenv('EMAIL_TEMPLATE_ID');

      // Retrieve sms notification by id and verify contents
      $response = $this->getTemplate( $templateId );

      //
      $response->shouldBeArray();
      $response->shouldHaveKey( 'id' );
      $response->shouldHaveKey( 'type' );
      $response->shouldHaveKey( 'created_at' );
      $response->shouldHaveKey( 'updated_at' );
      $response->shouldHaveKey( 'created_by' );
      $response->shouldHaveKey( 'version' );
      $response->shouldHaveKey( 'body' );
      $response->shouldHaveKey( 'subject' );

      $response['id']->shouldBeString();
      $response['id']->shouldBe( $templateId );
      $response['type']->shouldBeString();
      $response['type']->shouldBe( 'email' );
      $response['version']->shouldBeInteger();
      $response['body']->shouldBe( "Hello ((name))\r\n\r\nFunctional test help make our world a better place" );
      $response['subject']->shouldBeString();
      $response['subject']->shouldBe( 'Functional Tests are good' );
    }

    function it_receives_the_expected_response_when_looking_up_an_sms_template() {
      $templateId = getenv('SMS_TEMPLATE_ID');

      // Retrieve sms notification by id and verify contents
      $response = $this->getTemplate( $templateId );

      //
      $response->shouldBeArray();
      $response->shouldHaveKey( 'id' );
      $response->shouldHaveKey( 'type' );
      $response->shouldHaveKey( 'created_at' );
      $response->shouldHaveKey( 'updated_at' );
      $response->shouldHaveKey( 'created_by' );
      $response->shouldHaveKey( 'version' );
      $response->shouldHaveKey( 'body' );
      $response->shouldHaveKey( 'subject' );

      $response['id']->shouldBeString();
      $response['id']->shouldBe( $templateId );
      $response['type']->shouldBeString();
      $response['type']->shouldBe( 'sms' );
      $response['version']->shouldBeInteger();
      $response['body']->shouldBe( "Hello ((name))\r\n\r\nFunctional Tests make our world a better place" );
      $response['subject']->shouldBeNull();
    }

    function it_receives_the_expected_response_when_looking_up_a_template_version() {
      $templateId = getenv('SMS_TEMPLATE_ID');
      $version = 2;

      // Retrieve sms notification by id and verify contents
      $response = $this->getTemplateVersion( $templateId, $version );

      //
      $response->shouldBeArray();
      $response->shouldHaveKey( 'id' );
      $response->shouldHaveKey( 'type' );
      $response->shouldHaveKey( 'created_at' );
      $response->shouldHaveKey( 'updated_at' );
      $response->shouldHaveKey( 'created_by' );
      $response->shouldHaveKey( 'version' );
      $response->shouldHaveKey( 'body' );
      $response->shouldHaveKey( 'subject' );

      $response['id']->shouldBeString();
      $response['id']->shouldBe( $templateId );
      $response['type']->shouldBeString();
      $response['type']->shouldBe( 'sms' );
      $response['created_at']->shouldBeString();
      $response['created_by']->shouldBeString();
      $response['version']->shouldBeInteger();
      $response['version']->shouldBe( $version );
      $response['body']->shouldBe("Functional Tests make our world a better place");
      $response['subject']->shouldBeNull();
    }

    function it_receives_the_expected_response_when_looking_up_all_templates() {

      // Retrieve all notifications and verify each is correct (email & sms)
      $response = $this->listTemplates();

      $response->shouldHaveKey('templates');
      $response['templates']->shouldBeArray();

      $templates = $response['templates'];
      $total_notifications_count = count($templates->getWrappedObject());

      for( $i = 0; $i < $total_notifications_count; $i++ ) {

          $template = $templates[$i];

          $template->shouldBeArray();
          $template->shouldHaveKey( 'id' );
          $template->shouldHaveKey( 'type' );
          $template->shouldHaveKey( 'created_at' );
          $template->shouldHaveKey( 'updated_at' );
          $template->shouldHaveKey( 'created_by' );
          $template->shouldHaveKey( 'version' );
          $template->shouldHaveKey( 'body' );
          $template->shouldHaveKey( 'subject' );

          $template['id']->shouldBeString();
          $template['created_at']->shouldBeString();
          $template['created_by']->shouldBeString();
          $template['version']->shouldBeInteger();
          $template['body']->shouldBeString();

          $template['type']->shouldBeString();
          $template_type = $template['type']->getWrappedObject();

          if ( $template_type == "sms" ) {
            $template['subject']->shouldBeNull();

          } elseif ( $template_type == "email" || $template_type == "letter" ) {

            $template['subject']->shouldBeString();

          }
      }

    }

    function it_receives_the_expected_response_when_previewing_a_template() {
      $templateId = getenv('SMS_TEMPLATE_ID');

      // Retrieve sms notification by id and verify contents
      $response = $this->previewTemplate( $templateId, [ 'name' => 'Foo' ]);

      //
      $response->shouldBeArray();
      $response->shouldHaveKey( 'id' );
      $response->shouldHaveKey( 'type' );
      $response->shouldHaveKey( 'version' );
      $response->shouldHaveKey( 'body' );
      $response->shouldHaveKey( 'subject' );

      $response['id']->shouldBeString();
      $response['id']->shouldBe( $templateId );
      $response['type']->shouldBeString();
      $response['type']->shouldBe( 'sms' );
      $response['version']->shouldBeInteger();
      $response['body']->shouldBe("Hello Foo\r\n\r\nFunctional Tests make our world a better place");
      $response['subject']->shouldBeNull();
    }

    function it_receives_the_expected_response_when_sending_an_sms_notification_with_invaild_smsSenderId(){
      $this->shouldThrow('Alphagov\Notifications\Exception\ApiException')->duringSendSms(
        getenv('FUNCTIONAL_TEST_EMAIL'),
        getenv('SMS_TEMPLATE_ID'),
        [ "name" => "Foo" ],
        '',
        'invlaid_uuid'
      );
    }

    function it_receives_the_expected_response_when_sending_an_sms_notification_with_valid_seender_id(){
      $this->beConstructedWith([
        'baseUrl'       => getenv('NOTIFY_API_URL'),
        'apiKey'        => getenv('API_SENDING_KEY'),
        'httpClient'    => new \Http\Adapter\Guzzle6\Client
      ]);

      $response = $this->sendSms(
        getenv('FUNCTIONAL_TEST_NUMBER'),
        getenv('SMS_TEMPLATE_ID'), [
            "name" => "Foo"
        ],
        'ref123',
        getenv('SMS_SENDER_ID')
      );

      $response->shouldBeArray();
      $response->shouldHaveKey( 'id' );
      $response['id']->shouldBeString();

      $response->shouldHaveKey( 'reference' );

      $response->shouldHaveKey( 'content' );
      $response['content']->shouldBeArray();
      $response['content']->shouldHaveKey( 'from_number' );
      $response['content']['from_number']->shouldBeString();
      $response['content']->shouldHaveKey( 'body' );
      $response['content']['body']->shouldBeString();
      $response['content']['body']->shouldBe("Hello Foo\r\n\r\nFunctional Tests make our world a better place");

      $response->shouldHaveKey( 'template' );
      $response['template']->shouldBeArray();
      $response['template']->shouldHaveKey( 'id' );
      $response['template']['id']->shouldBeString();
      $response['template']->shouldHaveKey( 'version' );
      $response['template']['version']->shouldBeInteger();
      $response['template']->shouldHaveKey( 'uri' );

      $response->shouldHaveKey( 'uri' );
      $response['uri']->shouldBeString();
    }
 
    function it_receives_the_expected_response_when_sending_a_letter_notification(){

      $payload = [
          'template_id'=> getenv('LETTER_TEMPLATE_ID'),
          'personalisation' => [ 
              'name'=>'Fred',
              'address_line_1' => 'Foo',
              'address_line_2' => 'Bar',
              'postcode' => 'Baz'
          ],
          'reference'=>'client-ref'
      ];

      //---------------------------------
      // Perform action

      $response = $this->sendLetter( $payload['template_id'], $payload['personalisation'], $payload['reference']);

      $response->shouldBeArray();
      $response->shouldHaveKey( 'id' );
      $response['id']->shouldBeString();

      $response->shouldHaveKey( 'reference' );
      $response['reference']->shouldBe("client-ref");

      $response->shouldHaveKey( 'content' );
      $response['content']->shouldBeArray();
      $response['content']->shouldHaveKey( 'body' );
      $response['content']['body']->shouldBeString();
      $response['content']['body']->shouldBe("Hello Foo");
      $response['content']->shouldHaveKey( 'subject' );
      $response['content']['subject']->shouldBeString();
      $response['content']['subject']->shouldBe("Main heading");

      $response->shouldHaveKey( 'template' );
      $response['template']->shouldBeArray();
      $response['template']->shouldHaveKey( 'id' );
      $response['template']['id']->shouldBeString();
      $response['template']->shouldHaveKey( 'version' );
      $response['template']['version']->shouldBeInteger();
      $response['template']->shouldHaveKey( 'uri' );

      $response->shouldHaveKey( 'uri' );
      $response['uri']->shouldBeString();

      $response->shouldHaveKey( 'scheduled_for' );
      $response['scheduled_for']->shouldBe(null);
    }

    function it_exposes_error_details() {
      $caught = false;
      try {
        // missing personalisation
        $response = $this->sendEmail( getenv('FUNCTIONAL_TEST_EMAIL'), getenv('EMAIL_TEMPLATE_ID'), [] );
      } catch (ApiException $e) {
        assert('$e->getCode() == 400;');
        assert('$e->getErrorMessage() == \'BadRequestError: "Missing personalisation: name"\';');
        assert('$e->getErrors()[0][\'error\'] == \'BadRequestError\'');
        $caught = true;
      }
      assert('$caught == true;');
    }

    function it_receives_the_expected_response_when_looking_up_received_texts() {
      $this->beConstructedWith([
        'baseUrl'       => getenv('NOTIFY_API_URL'),
        'apiKey'        => getenv('INBOUND_SMS_QUERY_KEY'),
        'httpClient'    => new \Http\Adapter\Guzzle6\Client
      ]);

      $response = $this->listReceivedTexts();

      $response->shouldHaveKey('received_text_messages');
      $response['received_text_messages']->shouldBeArray();

      $received_texts = $response['received_text_messages'];

      $received_texts_count = count($received_texts->getWrappedObject());

      assert('$received_texts_count > 0;');

      for( $i = 0; $i < $received_texts_count; $i++ ) {

          $received_text = $received_texts[$i];
          $received_text->shouldBeArray();
          $received_text->shouldHaveKey( 'id' );
          $received_text->shouldHaveKey( 'service_id' );
          $received_text->shouldHaveKey( 'created_at' );
          $received_text->shouldHaveKey( 'user_number' );
          $received_text->shouldHaveKey( 'notify_number' );
          $received_text->shouldHaveKey( 'content' );
          
          $received_text['id']->shouldBeString();
          $received_text['service_id']->shouldBeString();
          $received_text['created_at']->shouldBeString();
          $received_text['user_number']->shouldBeString();
          $received_text['notify_number']->shouldBeString();
          $received_text['content']->shouldBeString();
      }
    }
}
