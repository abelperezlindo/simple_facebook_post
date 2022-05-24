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
      $saved_content_id = \Drupal::state()->get('analisis_autopost.content', '');

      if(empty($saved_content_id)){
        $form['content_box'] = [
          '#type'   => 'details',
          '#title'  => t('Configuración del contenido'),
          '#description' => 'Seleccione el tipo de contenido y los campos que serán usados para compartir',
        ];
        $form['content_box']['content'] = [
          '#type'   => 'select',
          '#title'  => t('Tipo de contenido'),
          '#options' => $types,
          '#empty_option'  => t('Seleccionar'),
          '#description'  => t('Ingrese el nombre de sistema del el tipo de contenido que desea utilizar'),
        ];
        $form['content_box']['save_content_id'] = [
          '#type' => 'submit',
          '#name' => 'save_content_id',
          '#value' => t('save content config')
        ]; 
      }
      else {

        $options = $this->_getFieldOptions($saved_content_id);
        $form['content_box'] = [
          '#type'   => 'details',
          '#title'  => t('Configuración del contenido'),
          '#description' => t( 
            'El tipo de contenido usado es :@content_type, usted puede seleccionar 
            los campos a partir de los cuales se creará la publicación social.',
            [':@content_type' => $types[$saved_content_id]]
          ),
        ];
        $form['content_box']['title'] = [
          '#type'   => 'select',
          '#options' => $options['title'],
          '#title'  => t('Campo de titulo '),
          '#description'  => t('Ingrese el nombre de sistema del del campo que desea utilizar como titulo.'),
          '#default_value' => \Drupal::state()->get('analisis_autopost.title', ''),
          '#empty_option'  => t('Seleccionar')
        ];
  
        $form['content_box']['title_suffix'] = [
          '#type'   => 'textfield',
          '#title'  => t('Title suffx'),
          '#description'  => t('Ingrese un texto fijo o un token para concatenar al final del tiulo.'),
          '#default_value' => \Drupal::state()->get('analisis_autopost.title_suffix', ''),
          '#empty_option'  => t('Seleccionar')
        ];
        $form['content_box']['body'] = [
          '#type'   => 'select',
          '#title'  => t('Campo de texto'),
          '#options' => $options['body'],
          '#description'  => t('Ingrese el nombre de sistema del del campo que desea utilizar como fuente del texto.'),
          '#default_value' => \Drupal::state()->get('analisis_autopost.body', ''),
          '#empty_option'  => t('Seleccionar')
  
        ];
        $form['content_box']['media'] = [
          '#type'   => 'select',
          '#title'  => t('Campo multimedia'),
          '#options' => $options['image'],
          '#description'  => t('Ingrese el nombre de sistema del del campo que desea utilizar como fuente del texto.'),
          '#default_value' => \Drupal::state()->get('analisis_autopost.media', ''),
          '#empty_option'  => t('Seleccionar')
        ];
        $form['content_box']['options']['delete'] = [
          '#type' => 'submit',
          '#name' => 'delete_content_config',
          '#value' => t('Eliminar configuración')
        ]; 
        $form['content_box']['options']['perview'] = [
          '#type' => 'submit',
          '#name' => 'content_config_preview',
          '#value' => t('Guardar y previsualizar')
        ];
        $preview = \Drupal::state()->get('analisis_autopost.preview_markup', '');
        if(!empty($preview)){
          $form['content_box']['preview_markup'] = [
            '#markup' => $preview,
          ];
          \Drupal::state()->set('analisis_autopost.preview_markup', '');
        }
        
        
      }

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





      if($trigger['#type'] === 'submit' && $trigger['#name'] =='save_content_id'){
        \Drupal::state()->set('analisis_autopost.content', $form_state->getValue('content'));
        return;
      }

      if($trigger['#type'] === 'submit' && $trigger['#name'] =='delete_content_config'){
        \Drupal::state()->set('analisis_autopost.content', '');
        \Drupal::state()->set('analisis_autopost.title', '');
        \Drupal::state()->set('analisis_autopost.body', '');
        \Drupal::state()->set('analisis_autopost.title_suffix', '');
        \Drupal::state()->set('analisis_autopost.media', '');
        return;
      }


      if($trigger['#type'] === 'submit' && $trigger['#name'] =='content_config_preview'){

        //$config_manager::setContentConfig($form_state->getValues());
        \Drupal::state()->set('analisis_autopost.title', $form_state->getValue('title'));
        \Drupal::state()->set('analisis_autopost.body', $form_state->getValue('body'));
        \Drupal::state()->set('analisis_autopost.title_suffix', $form_state->getValue('title_suffix'));
        \Drupal::state()->set('analisis_autopost.media', $form_state->getValue('media'));
        $saved_content_id = \Drupal::state()->get('analisis_autopost.content', '');
        $query = \Drupal::entityQuery('node');
        $query
          ->condition('type', $saved_content_id)
          ->sort('changed', 'DESC')
          ->range(0, 1);

        $nid      = $query->execute();
        if(empty($nid)){
          //\Drupal::messenger()->addStatus(t('No existe contenido del tipo :@type', [':@type' => $saved_content_id]));
          $form_state->setValue();
          \Drupal::state()->set(
            'analisis_autopost.preview_markup', 
            'No existe contenido del tipo seleccionado.'
          );
          return;
        }

        $node     = \Drupal::entityTypeManager()->getStorage('node')->load(array_pop($nid));
        $message  = [];
        $message[] = '<div class="post-preview-wrapper"><p>Previsualizando nodo de tipo ' . $saved_content_id . ' como ejemplo</p>'; 
        $message[] = '<div class="post-preview-card">';
        if($node->hasField($form_state->getValue('media'))){
          $message[] = '<div class="post-preview-img" title="Post image">';

          /** @var \Drupal\file\Plugin\Field\FieldType\FileFieldItemList $media */
          $ref_list = $node->{$form_state->getValue('media')}->referencedEntities(); /** @todo problema aca */
          if(isset($ref_list[0])){
            /** @var \Drupal\file\Entity\File $file */
            $file_uri = $ref_list[0]->getFileUri();

            // Remove the if-else when core_version_requirement >= 9.3 for this module.
            if(\Drupal::hasService('file_url_generator')) {
              $generator = \Drupal::service('file_url_generator');
              $image_uri = \Drupal\image\Entity\ImageStyle::load('medium')->buildUrl($file_uri);
              $img_url = $generator->generateAbsoluteString($image_uri);

            }
            else {
              $img_url = file_url_transform_relative(file_create_url($file_uri));
            }

          } 
          if(!empty($img_url)){
            $message[] = '<img src="' . $img_url . '">';
          }
          $message[] = '</div>';
        }

        if($node->hasField($form_state->getValue('title'))){
          $message[] = '<div class="post-preview-title title="Post Title"">';
          $title =  $node->{$form_state->getValue('title')};
          $field_type = $title->getFieldDefinition()->getType();
          if(in_array($field_type, ['string', 'text'])){
            $message[] = '<h5>' . $title->value . ' ' . $form_state->getValue('title_suffix') . '</h5>';
          }
          $message[] = '</div>';
       
        }

        if($node->hasField($form_state->getValue('body'))){
          $message[] = '<div class="post-preview-body title="Post Body"">';
          $body =  $node->{$form_state->getValue('body')};
          $field_type = $body->getFieldDefinition()->getType();
          if(in_array($field_type, ['string', 'text', 'text_long', 'text_with_summary'])){
            $message[] = '<p>' . $body->value . '</p>';
          }
          $message[] = '</div>';
        }
        $message[] = '</div></div>';
        \Drupal::state()->set('analisis_autopost.preview_markup', implode($message));

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

    public function reloadContentBox(array $form, FormStateInterface $form_state){
      $selected_content = \Drupal::state()->get('analisis_autopost.content', 'nothing');
      if($selected_content == 'nothing'){
        return  [];
      }
      return $form['content_box']['options'];
    }
 
    private function _getFieldOptions(string $content_type){
      $options = ['title' => [], 'body' => [], 'image' => []];
      if($content_type == 'nothing'){
        return $options;
      }
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