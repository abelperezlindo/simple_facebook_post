<?php
namespace Drupal\analisis_autopost\Form;

use Drupal\Core\Entity;
use \Facebook\Facebook as Facebook;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\ConfigFormBase;
use \Facebook\Exception\SDKException;
use Drupal\field\FieldConfigInterface;
use \Abraham\TwitterOAuth\TwitterOAuth;
use Drupal\Core\Form\FormStateInterface;
use \Facebook\Exception\ResponseException;
use Drupal\analisis_autopost\Twitter\TwitterPost;
use Drupal\analisis_autopost\Facebook\FacebookPost;
use Drupal\config_translation\FormElement\FormElementBase;

class ConfigurarModulo extends ConfigFormBase {

    public function getFormId()
    {
        return 'analisis_autopost_form';
        
    }

    public function getEditableConfigNames(){
      return [
        'analisis_autopost.settings'  
      ];
    }


    public function buildForm(array $form, FormStateInterface $form_state ){

      // Form constructor.
        $form = parent::buildForm($form, $form_state);
        $config_manager = \Drupal::service('analisis_autopost.config_manager');

      $entityTypeManager = \Drupal::service('entity_type.manager');

      $types = [];


      $contentTypes = $entityTypeManager->getStorage('node_type')->loadMultiple();
      foreach ($contentTypes as $contentType) {
          $types[$contentType->id()] = $contentType->label();
      }

      $form['description'] = [
        '#markup' => t('<p>Este modulo es usado para compartir noticias del sitio en Facebook. El contenido que se comparte es "Noticia" y cada vez que se crea una nueva noticia se publica en facebook</p>
        <p>Se publica el titulo, el resumen del cuerpo, o una parte de el, la imagen principal de la noticia y el enlace.<p>
        <p>A continuación se debe ingresar información de la app de facebook, esto es necesario para poder comunicarse con la api de Facebook</p>')
      ];

      $form['content_box'] = [
        '#type'   => 'details',
        '#title'  => t('Configuración del contenido'),
        '#description' => 'Ingrese el tipo de contenido y los campos que serán usados para compartir',
        '#prefix'   => '<div id="config-content-box">',
        '#suffix'   => '</div>',
      ];
  
      $selected_content = $form_state->getValue('content') ?? null;
      if(is_null($selected_content)){
        $selected_content = \Drupal::state()->get('analisis_autopost.content', '');
      }

      $form['content_box']['content'] = [
        '#type'   => 'select',
        '#title'  => t('Tipo de contenido'),
        '#options' => $types,
        '#description'  => t('Ingrese el nombre de sistema del el tipo de contenido que desea utilizar'),
        '#default_value' => $selected_content,
        '#ajax'     => ['callback' => [$this, 'reloadContntBox'], 'wrapper'   => 'config-content-box']
      ];

      $options = ['title' => [], 'body' => [], 'image' => []];
      $selected_title = '';
      $selected_body = '';
      $selected_image = '';

      if(empty($selected_content)){
        $options = $this->_getFieldOptions($selected_content);
        $selected_title = \Drupal::state()->get('analisis_autopost.title', '');
        $selected_body = \Drupal::state()->get('analisis_autopost.body', '');
        $selected_image = \Drupal::state()->get('analisis_autopost.media', '');
      }

      
      $form['content_box']['title'] = [
        '#type'   => 'select',
        '#options' => $options['title'],
        '#title'  => t('Campo de titulo '),
        '#description'  => t('Ingrese el nombre de sistema del del campo que desea utilizar como titulo.'),
        '#default_value' => $selected_title
      ];
      $form['content_box']['title_suffix'] = [
        '#type'   => 'textfield',
        '#title'  => t('Title suffx'),
        '#description'  => t('Ingrese un texto fijo o un token para concatenar al final del tiulo.'),
        '#default_value' => \Drupal::state()->get('analisis_autopost.title_suffix', '')

      ];
      $form['content_box']['body'] = [
        '#type'   => 'select',
        '#title'  => t('Campo de texto'),
        '#options' => $options['body'],
        '#description'  => t('Ingrese el nombre de sistema del del campo que desea utilizar como fuente del texto.'),
        '#default_value' => $selected_body

      ];
      $form['content_box']['media'] = [
        '#type'   => 'select',
        '#title'  => t('Campo multimedia'),
        $options => $options['image'],
        '#description'  => t('Ingrese el nombre de sistema del del campo que desea utilizar como fuente del texto.'),
        '#default_value' => $selected_image
      ];
      $form['content_box']['perview'] = [
        '#type' => 'submit',
        '#name' => 'content_preview',
        '#value' => 'Previsualizar'
      ];

      $form['facebook'] = [
        '#type'   => 'details',
        '#title'  => t('Configuración del posteo automatico en facebook')
      ];
      $form['facebook']['facebook_app_id'] = [
        '#type'         => 'textfield',
        '#title'        => t('App id'),
        '#description'  => t('Id de la aplicacion de Facebook'),
        'Aattributes'   => ['placeholder' => 'App id'],
        '#default_value' => \Drupal::state()->get('analisis_autopost.facebook_app_id', '')
      ];
      $form['facebook']['facebook_app_secret'] = [
        '#type'         => 'textfield',
        '#title'        => t('App secret'),
        '#description'  => t('App secret de la aplicacion de Facebook'),
        'Aattributes'   => ['placeholder' => 'App secret'],
        '#default_value' => \Drupal::state()->get('analisis_autopost.facebook_app_secret', '')
      ];
      $form['facebook']['facebook_page_id'] = [
        '#type'         => 'textfield',
        '#title'        => t('Id de la página'),
        '#description'  => t('Id de la página de Facebook en la cual se va a postear'),
        'Aattributes'   => ['placeholder' => 'App id'],
        '#default_value' => \Drupal::state()->get('analisis_autopost.facebook_page_id', '')
      ];
      $form['facebook']['facebook_api_version'] = [
        '#type'         => 'textfield',
        '#title'        => t('Version de la api'),
        '#description'  => t('Ingrese la version de la api a usar para interactuar con facebook'),
        'Aattributes'   => ['placeholder' => 'v13.0'],
        '#default_value' => \Drupal::state()->get('analisis_autopost.facebook_api_version', 'v13.0')
      ];
      $form['facebook']['facebook_permisos'] = [
        '#type'         => 'textfield',
        '#title'        => t('Permisos necesarios'),
        '#description'  => t('Ingrese los permisos necesarios para el funcionamiento de este modulo separados por coma ",", los permisos cambian dependiendo de la version de la api que se usa.'),
        'Aattributes'   => ['placeholder' => 'v13.0'],
        '#default_value' => \Drupal::state()->get('analisis_autopost.facebook_permisos', 'email')
      ];

      $default_access_token = $config_manager::getFbUserAccessToken();
      $fb_is_configured = $config_manager::isFbConfigured();
      $fb_config = $config_manager::getFacebookConfig();
      $markup = [];
      if($fb_is_configured) {
        $fb = new Facebook([
          'app_id'                => $fb_config['facebook_app_id'],
          'app_secret'            => $fb_config['facebook_app_secret'],
          'default_graph_version' => $fb_config['facebook_api_version'],
        ]);
    
        $helper = $fb->getRedirectLoginHelper();
        $permissions = [ $fb_config['facebook_permisos'] ];
        $login_url =  $helper->getLoginUrl($GLOBALS['base_url'] . '/facebook/fb-callback', $permissions);

        $markup[] = '<p>Inicie sesión con un usuario de Facebook que tenga permisos para publicar en la pagina ingresada en la configuracion, esto es necesario para el funcionamiento de este módulo</p><p>Siga este enlace y podra iniciar desde facebook <a href="' . $login_url . '"> haciendo clic</a></p>';
      }

      if(!is_null($default_access_token) && $fb_is_configured){
        $user_name = '';
        $fb = new Facebook([
          'app_id'                => $fb_config['facebook_app_id'],
          'app_secret'            => $fb_config['facebook_app_secret'],
          'default_graph_version' => $fb_config['facebook_api_version'],
          'default_access_token'  => $default_access_token
        ]);

        try {
          $response = $fb->get('/me?fields=name');
          /** @var  \Facebook\GraphNode\GraphUser $user */
          $user = $response->getGraphUser(); // obtenemos un objeto graph tipo usuario
          $user_name = $user->getName();

        } catch(ResponseException $e) {
          $err = 'Graph returned an error: ' . $e->getMessage();
          \Drupal::logger('analisis_autopost')->warning($err);
          $markup[] = 'Se produjo un error al intentar obtener información del usuario, por favor compruebe los datos ingresados sean correctos e intente iniciar sesion con Facebook.';
          $markup[] = '<pre>' . $err . '</pre>';

        } catch(SDKException $e) {
          $err = 'Facebook SDK returned an error: ' . $e->getMessage();
          \Drupal::logger('analisis_autopost')->warning($err);
          $markup[] = 'Se produjo un error al intentar obtener información del usuario, por favor compruebe los datos ingresados sean correctos e intente iniciar sesion con Facebook.';
          $markup[] = '<pre>' . $err . '</pre>';
        }

        if(!empty($user_name)){
          $markup[] ='<p>Actualmente el sistema tiene acceso a la cuenta de fecebook de ' . $user_name . '</p>';
        }
      } 
      
      $form['facebook']['description'] = [
        '#markup' => t(implode($markup))
      ];
      
      /** @todo save data for twitter api connect */ 
      $form['twitter'] = [
        '#type'   => 'details',
        '#title'  => t('Configuración del posteo automatico en Twiter')
      ];
      $form['twitter']['twitter_consumer_key'] = [
        '#type'         => 'textfield',
        '#title'        => t('Consumer Key'),
        '#description'  => t('Ingrese consumer key'),
        'Aattributes'   => ['placeholder' => 'v13.0'],
        '#default_value' => \Drupal::state()->get('analisis_autopost.twitter_consumer_key')
      ];
      $form['twitter']['twitter_consumer_secret'] = [
        '#type'         => 'textfield',
        '#title'        => t('Consumer Secret'),
        '#description'  => t('Ingrese consumer secret'),
        'Aattributes'   => ['placeholder' => 'v13.0'],
        '#default_value' => \Drupal::state()->get('analisis_autopost.twitter_consumer_secret')
      ];
      $form['twitter']['twitter_access_token'] = [
        '#type'         => 'textfield',
        '#title'        => t('Access Token'),
        '#description'  => t('Ingrese access token'),
        'Aattributes'   => ['placeholder' => 'v13.0'],
        '#default_value' => \Drupal::state()->get('analisis_autopost.twitter_access_token')
      ];
      $form['twitter']['twitter_access_token_secret'] = [
        '#type'         => 'textfield',
        '#title'        => t('Access Token Secret'),
        '#description'  => t('Ingrese access token secret'),
        'Aattributes'   => ['placeholder' => 'v13.0'],
        '#default_value' => \Drupal::state()->get('analisis_autopost.twitter_access_token_secret')
      ];
      $form['twitter']['twitter_test_connection'] = [
        '#type'  => 'submit',
        '#name'  => 'action_twitter_test',
        '#value' => 'Comprobar',
      ];

      return $form;
    }
  




    /**
     * { @inheritDoc }
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
      $trigger = $form_state->getTriggeringElement();
      $config_manager = \Drupal::service('analisis_autopost.config_manager');
      // content_preview
      if($trigger['#type'] === 'submit' && $trigger['#name'] =='content_preview'){

        $config_manager::setContentConfig($form_state->getValues());
        //$query = \Drupal::entityQuery($form_state->getValue('content'));
        
        $query = \Drupal::entityQuery('node');
        $query
          ->condition('type', $form_state->getValue('content'))
          ->sort('changed', 'DESC')
          ->range(0, 1);

        $nid      = $query->execute();
        $node     = \Drupal::entityTypeManager()->getStorage('node')->load(array_pop($nid));
        $message  = [];
        //$definitions  = \Drupal::entityTypeManager()->getDefinition('field');

        $node->hasField('field_tags');

        // Returns an array with named keys for all fields and their
        // definitions. For example the ‘image’ field.
        $field_definitions = $node->getFieldDefinitions();

       
        /*
        \Drupal::entityTypeManager()
          ->getStorage('field_storage_config')
          ->load($entity_type_id . '.' . $field_name);
          */
        if(isset($node->{$form_state->getValue('title')})){
          $message[] = 'Tine titulo: ' . $node->{$form_state->getValue('title')}->value;

        }
        if(isset($node->{$form_state->getValue('body')})){
          
          /** @var \Drupal\Core\Field\FieldItemList $body_field */
          $body_field = $node->{$form_state->getValue('body')};
          $field_definition = $body_field->getFieldDefinition(); /** @var Drupal\field\Entity\FieldConfig $field_definition */
          if( in_array($field_definition->getType(), ['text', 'text_long', 'text_with_summary'])  ){
            $message[] = 'Tine body: ' . $node->{$form_state->getValue('body')}->value;
          }
        }
        
        if(isset($node->{$form_state->getValue('media')})){
          /** @var \Drupal\file\Plugin\Field\FieldType\FileFieldItemList $media */
          $ref_list = $node->{$form_state->getValue('media')}->referencedEntities();
         
         
          if(isset($ref_list[0])){
             /** @var \Drupal\file\Entity\File $file */
            $file_uri = $ref_list[0]->getFileUri();

            // Remove the if-else when core_version_requirement >= 9.3 for this module.
            if(\Drupal::hasService('file_url_generator')) {
              $generator = \Drupal::service('file_url_generator');
              $url = $generator->generateAbsoluteString($file_uri);
            }
            else {
              $url = file_url_transform_relative(file_create_url($file_uri));
            }

          } 
          $message[] = 'Tine media: ' . $url;

        }   

        \Drupal::messenger()->addStatus(t(implode($message)));
        
        

      }

      if($trigger['#type'] === 'submit' && $trigger['#name'] =='action_facebook_test'){
        
        $config_manager::setFacebookConfig($form_state->getValues());

        $default_access_token = $config_manager::getFbUserAccessToken();
        $fb = new Facebook([
          'app_id'                => $form_state->getValue('facebook_app_id'),
          'app_secret'            => $form_state->getValue('facebook_app_secret'),
          'default_graph_version' => $form_state->getValue('facebook_api_version'),
          'default_access_token'  => $default_access_token
        ]);

        if(!is_null($default_access_token)){
          try {
            $response = $fb->get('/me?fields=name');
          } catch(ResponseException $e) {
            $err = 'Graph returned an error: ' . $e->getMessage();
            \Drupal::logger('analisis_autopost')->warning($err);
            return false;
          } catch(SDKException $e) {
            $err = 'Facebook SDK returned an error: ' . $e->getMessage();
            \Drupal::logger('analisis_autopost')->warning($err);
            return false;
          }
          /** @var  \Facebook\GraphNode\GraphUser $user */
          $user = $response->getGraphUser(); // obtenemos un objeto graph tipo usuario
          $user_name = $user->getName();
        }
      }

      if($trigger['#type'] === 'submit' && $trigger['#name'] =='action_twitter_test'){

        $config_manager::setTwitterConfig($form_state->getValues());

        $tw = new TwitterOAuth(
          $form_state->getValue('twitter_consumer_key'),
          $form_state->getValue('twitter_consumer_secret'), 
          $form_state->getValue('twitter_access_token'), 
          $form_state->getValue('twitter_access_token_secret')
        );
    
        $tw->setApiVersion('2');

        $uid = explode('-', $form_state->getValue('twitter_access_token'))[0];
        $content = $tw->get('users', ['ids' => $uid]);
        if(isset($content->errors)){
          foreach($content->errors as $error){
            \Drupal::messenger()->addWarning(
              t(
                'La comprobación de la cuenta falló, Twitter retornó el siguiente 
                código de error @error_code: "@error_msg".',
                ['@error_code' => $error->code, '@error_msg' => $error->message]
              )
            );
          }
        } elseif(isset($content->data)){
          foreach($content->data as $data){
            \Drupal::messenger()->addStatus(t('Ok, @user.', ['@user' => $data->username]));
          }
        }
      }

      if($trigger['#type'] === 'submit' && $trigger['#id'] == 'edit-submit'){
        $config_manager::setAll($form_state->getValues());
        return parent::submitForm($form, $form_state);
      }   
    }

    /**
     * { @inheritDoc }
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
      

    }

    public function reloadContntBox(array $form, FormStateInterface $form_state){
      return $form['content_box'];
    }
 
    private function _getFieldOptions(string $content_type){
      $options = ['title' => [], 'body' => [], 'image' => []];
      //EntityFieldManager::getFieldDefinitions()
      //foreach()
      /** @var \Drupal\Core\Entity\EntityFieldManager $fieldManager */
      $fieldManager =  \Drupal::service('entity_field.manager');
      $fields = $fieldManager->getFieldDefinitions('node', $content_type );
      foreach($fields as $key => $value){
        if(in_array($value->getType(), ['string', 'text', 'text_with_summary', 'string_long'])){
          $options['title'][$key] = $key;
          $options['body'][$key] = $key;
        }
        if($value->getType() === 'image'){
          $options['image'][$key] = $key;
        }
      }
      return $options;
    }
}