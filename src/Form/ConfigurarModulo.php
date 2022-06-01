<?php
namespace Drupal\simple_facebook_post\Form;


use \Facebook\Facebook as Facebook;
use Drupal\Core\Form\ConfigFormBase;
use \Facebook\Exception\SDKException;
use Drupal\Core\Form\FormStateInterface;
use \Facebook\Exception\ResponseException;


class ConfigurarModulo extends ConfigFormBase {

    public function getFormId()
    {
        return 'simple_facebook_post';
        
    }

    public function getEditableConfigNames(){
      return [
        'simple_facebook_post.settings'  
      ];
    }


    public function buildForm(array $form, FormStateInterface $form_state ){

      // Form constructor.
      $form = parent::buildForm($form, $form_state);
      /** @var \Drupal\Core\Utility\Token $token */
      $tokens = \Drupal::token();
      $config_manager   = \Drupal::service('simple_facebook_post.config_manager');
      $types            = $config_manager::getNodeTypesIds();
      $saved_content_id = $config_manager::get('content');
      $image_styles     = $config_manager::getImageStylesOptions();

      $form['sections'] = [
        '#type'         => 'vertical_tabs',
        '#title'        => t('Settings'),
        '#default_tab'  =>'edit-content-box'
      ];

      if(empty($saved_content_id)){
        $form['content_box'] = [
          '#type'         => 'details',
          '#title'        => t('Content Settings'),
          '#group'        => 'sections',
          '#description'  => t('
            Select the type of content and the 
            fields that will be used for sharing on 
            social networks when the content is created.'
          ),
        ];
        $form['content_box']['content'] = [
          '#type'         => 'select',
          '#title'        => t('Content type'),
          '#options'      => $types,
          '#empty_option' => t('Select'),
          '#description'  => t('
            Select the type of content you want to use to be published 
            on social networks at the time of its creation.
          '),
        ];
        $form['content_box']['save_content_id'] = [
          '#type'   => 'submit',
          '#name'   => 'save_content_id',
          '#value'  => t('save content config')
        ]; 
      }
      else {
        $options = $config_manager::getFieldsOptions($saved_content_id);
        $form['content_box'] = [
          '#type'         => 'details',
          '#title'        => t('Content Settings'),
          '#group'        => 'sections',
          '#description'  => t( 
            'The content type used is :@content_type, you can select the fields from which the social post will be created.',
            [':@content_type' => $types[$saved_content_id]]
          ),
          
        ];
        $form['content_box']['text_options'] = [
          '#type'         => 'fieldset',
          '#title'        => t('Text options'),
          '#descriptions' => t(
            'Link title customization options are available only if you have 
            validated your website domain on Facebook.'
          ),
        ];
        $form['content_box']['text_options']['body'] = [
          '#type'           => 'select',
          '#title'          => t('Field to use to generate the text of the post'),
          '#options'        => $options['body'],
          '#default_value'  => $config_manager::get('body') ?? '',
          '#empty_option'   => t('Select'),
          '#description'    => t(
            'Select the field you want to use
            as a font for the body of the post. If the text is too long, it will be cut off.'
          ),
        ];
        $form['content_box']['text_options']['body_use_summary'] = [
          '#type'           => 'checkbox',
          '#title'          => t('Use summary if available for selected field in post body.'),
          '#default_value'  => $config_manager::get('body_use_summary') ?? '',
        ];

        $form['content_box']['link'] = [
          '#type'         => 'fieldset',
          '#title'        => t('Link options'),
          '#descriptions' => t(
            'Attach the link to the content'
          ),
        ];
        $form['content_box']['link']['share_link'] = [
          '#type'         => 'checkbox',
          '#title'        => t('Attach the link to the content'),
          '#default_value'  => $config_manager::get('share_url') ?? '',
        ];
        $form['content_box']['link']['title'] = [
          '#type'           => 'select',
          '#options'        => $options['title'],
          '#title'          => t('Field to use to generate the title of the post'),
          '#default_value'  =>  $config_manager::get('title') ?? '',
          '#empty_option'   => t('Select'),
          '#description'    => t(
            'Select the field you want to use as the title of the link. 
            Link title customization options are available only if you have 
            validated your website domain on Facebook.'
          ),
        ];
  
        $form['content_box']['link']['title_suffix'] = [
          '#type'           => 'textfield',
          '#title'          => t('Title suffx'),
          '#default_value'  => $config_manager::get('title_suffix') ?? '',
          '#empty_option'   => t('select'),
          '#description'    => t(
            'Enter a fixed text or a token to concatenate to the end of the title of link. 
            Link title customization options are available only if you have 
            validated your website domain on Facebook.'
          ),
        ];
        $form['content_box']['upload_images'] = [
          '#type'         => 'fieldset',
          '#title'        => t('Images Options'),
          '#descriptions' => t(
            'you can attach images to the post.'
          ),
        ];
        $form['content_box']['upload_images']['image'] = [
          '#type'           => 'select',
          '#title'          => t('Field to use to generate the image of the post'),
          '#options'        => $options['image'],
          '#description'    => t('Select the field you want to use for the post image.'),
          '#default_value'  => $config_manager::get('image') ?? '',
          '#empty_option'   => t('select')
        ];
        $form['content_box']['upload_images']['image_style'] = [
          '#type'           => 'select',
          '#title'          => t('Post Image Style'),
          '#options'        => $image_styles,
          '#default_value'  => $config_manager::get('image_style') ?? '',
          '#empty_option'   => t('Select'),
          '#description'    => t(
            'Enter the image style you want to use, 
            if you do not select any option the original image will be used.'
          ),
        ];
        $form['content_box']['options']['delete'] = [
          '#type'   => 'submit',
          '#name'   => 'delete_content_config',
          '#value'  => t('Delete this setting')
        ]; 
        $form['content_box']['options']['perview'] = [
          '#type'   => 'submit',
          '#name'   => 'content_config_preview',
          '#value'  => t('Save these settings and preview')
        ];
        $preview = $config_manager::get('preview_markup') ?? '';
        if(!empty($preview)){
          $form['content_box']['preview_markup'] = [
            '#markup' => $preview,
          ];
          $config_manager::set('preview_markup', ''); 
        }      
      }

      $form['facebook'] = [
        '#type'   => 'details',
        '#title'  => t('Facebook API Access Settings'),
        '#group'  => 'sections',
      ];
      $form['facebook']['facebook_app_id'] = [
        '#type'         => 'textfield',
        '#title'        => t('Facebook app id'),
        '#description'  => t('Facebook app id'),
        '#default_value' => $config_manager::get('facebook_app_id')
      ];
      $form['facebook']['facebook_app_secret'] = [
        '#type'         => 'textfield',
        '#title'        => t('Facebook app secret'),
        '#description'  => t('Facebook app secret'),
        '#default_value' => $config_manager::get('facebook_app_secret')
      ];
      $form['facebook']['facebook_page_id'] = [
        '#type'         => 'textfield',
        '#title'        => t('Facebook page id'),
        '#description'  => t('Id of the Facebook page in which the content of the site will be published'),
        '#default_value' => $config_manager::get('facebook_page_id')
      ];
      $form['facebook']['facebook_api_version'] = [
        '#type'         => 'textfield',
        '#title'        => t('Version de la api'),
        '#description'  => t('Enter the version of the API to use to interact with facebook'),
        '#default_value' => $config_manager::get('facebook_api_version') ?? 'v13.0'
      ];
      $form['facebook']['facebook_permissions'] = [
        '#type'           => 'textfield',
        '#title'          => t('Permissions'),
        '#default_value'  => $config_manager::get('facebook_permissions') ?? 'email',
        '#description'    => t(
          'Enter the necessary permissions to be able to post content on 
          a user-managed Facebook page that grants access to your facebook 
          account. Enter each permission separated by a comma ",".
          the permissions change depending on the version 
          of the api that is used. See the Facebook graph api 
          documentation for more information'),
      ];

      $form['facebook']['description'] = [
        '#markup'  => $this->_getFacebookConnectionStatus(),
      ];
      


      return $form;
    }
  
    /**
     * { @inheritDoc }
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
      $trigger = $form_state->getTriggeringElement();
      $config_manager = \Drupal::service('simple_facebook_post.config_manager');


      if($trigger['#type'] === 'submit' && $trigger['#name'] =='save_content_id'){
        $config_manager::set('content', $form_state->getValue('content'));
        return;
      }

      if($trigger['#type'] === 'submit' && $trigger['#name'] =='delete_content_config'){
        $config_manager::setMultiple([
          'content'           => '',
          'title'             => '',
          'body'              => '',
          'title_suffix'      => '',
          'image'             => '',
          'image_style'       => '',
          'body_use_summary'  => '',
        ]);
      
        return;
      }

      if($trigger['#type'] === 'submit' && $trigger['#name'] =='content_config_preview'){

        //$config_manager::setContentConfig($form_state->getValues());
        $config_manager::setMultiple([
          'title'             => $form_state->getValue('title'),
          'body'              => $form_state->getValue('body'),
          'title_suffix'      => $form_state->getValue('title_suffix'),
          'image'             => $form_state->getValue('image'),
          'image_style'       => $form_state->getValue('image_style'),
          'body_use_summary'  => $form_state->getValue('body_use_summary'),
        ]);

        $saved_content_id = $config_manager::get('content');
        $query = \Drupal::entityQuery('node');
        $query
          ->condition('type', $saved_content_id)
          ->sort('changed', 'DESC')
          ->range(0, 1);

        $nid      = $query->execute();
        if(empty($nid)){
          $config_manager::set('preview_markup', 'There is no content of the selected type.');
          return;
        }

        $node     = \Drupal::entityTypeManager()->getStorage('node')->load(array_pop($nid));
        $message  = [];
        $message[] = '<div class="post-preview-wrapper"><p>Previewing ' . $saved_content_id . ' type node as an example</p>'; 
        $message[] = '<div class="post-preview-card">';
        if($node->hasField($form_state->getValue('image'))){
          $message[] = '<div class="post-preview-img" title="Post image">';

          /** @var \Drupal\file\Plugin\Field\FieldType\FileFieldItemList $ref_list */
          $ref_list = $node->{$form_state->getValue('image')}->referencedEntities(); 
          if(isset($ref_list[0])){
            /** @var \Drupal\file\Entity\File $file */
            $file_uri = $ref_list[0]->getFileUri();
            if(!empty($form_state->getValue('image_style'))){
              
              $image_uri = \Drupal\image\Entity\ImageStyle::load($form_state->getValue('image_style'))
                ->buildUrl($file_uri);

            }
            else {
              $image_uri = $file_uri;
            }

            // Remove the if-else when core_version_requirement >= 9.3 for this module.
            if(\Drupal::hasService('file_url_generator')) {
              $generator = \Drupal::service('file_url_generator');
             
              $img_url = $generator->generateAbsoluteString($image_uri);

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
            
            if($form_state->getValue('body_use_summary') && !empty($body->summary)) {
              $message[] = '<p>' . $body->summary . '</p>';

            }
            else {
              $message[] = '<p>' . $body->value . '</p>';

            }
          }
          $message[] = '</div>';
        }
        $message[] = '</div></div>';
  
        $config_manager::set('preview_markup', implode($message));

      }

      if($trigger['#type'] === 'submit' && $trigger['#name'] =='action_facebook_test'){
        
        $config_manager::setMultiple([
          'facebook_app_id'       => $form_state->getValue('facebook_app_id'),
          'facebook_app_secret'   => $form_state->getValue('facebook_app_secret'),
          'facebook_page_id'      => $form_state->getValue('facebook_page_id'),
          'facebook_api_version'  => $form_state->getValue('facebook_api_version'),
          'facebook_permissions'  => $form_state->getValue('facebook_permissions'),
        ]);

        $default_access_token = $config_manager::get('facebook_user_acces_token');

        if(!is_null($default_access_token)){
          $fb = new Facebook([
            'app_id'                => $form_state->getValue('facebook_app_id'),
            'app_secret'            => $form_state->getValue('facebook_app_secret'),
            'default_graph_version' => $form_state->getValue('facebook_api_version'),
            'default_access_token'  => $default_access_token
          ]);

          try {
            $response = $fb->get('/me?fields=name');
          } catch(ResponseException $e) {
            $err = 'Graph returned an error: ' . $e->getMessage();
            \Drupal::logger('simple_facebook_post')->warning($err);
            return false;
          } catch(SDKException $e) {
            $err = 'Facebook SDK returned an error: ' . $e->getMessage();
            \Drupal::logger('simple_facebook_post')->warning($err);
            return false;
          }
          /** @var  \Facebook\GraphNode\GraphUser $user */
          $user = $response->getGraphUser(); // obtenemos un objeto graph tipo usuario
          $user_name = $user->getName();
          \Drupal::messenger()->addStatus(t('Ok @user', ['@user' => $user_name]));
        } else {
          \Drupal::messenger()->addWarning(t('User access token is not configured'));
        }

      }
    }
    /**
     * { @inheritDoc }
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
      

    }
    
    /**
     * @return \Drupal\Core\StringTranslation\TranslatableMarkup
     */
    protected function _getFacebookConnectionStatus(){
      $config_manager       = \Drupal::service('simple_facebook_post.config_manager');
      $default_access_token = $config_manager::get('facebook_user_acces_token');

      $fb_config = $config_manager::getMultiple([
        'facebook_app_id',
        'facebook_app_secret',
        'facebook_api_version',
        'facebook_permissions',
      ]);

      $markup = '';
      $replace = [];
      
      if(!empty($fb_config['facebook_app_id']) && !empty($fb_config ['facebook_app_secret'])) {
        $fb = new Facebook([
          'app_id'                => $fb_config['facebook_app_id'],
          'app_secret'            => $fb_config['facebook_app_secret'],
          'default_graph_version' => $fb_config['facebook_api_version'] ?? 'v13.0',
        ]);
    
        $helper = $fb->getRedirectLoginHelper();
        $permissions = [ empty($fb_config['facebook_permissions']) ? '' : $fb_config['facebook_permissions'] ];
        $login_url =  $helper->getLoginUrl($GLOBALS['base_url'] . '/facebook/fb-callback', $permissions);
        $replace['@login_url'] = $login_url;

        $markup .= '<p> Sign in with a Facebook user
        that you have permissions to publish on the page
        entered in the configuration, this is necessary
        for the operation of this module</p>
        <p>You can start from facebook 
        <a href="@login_url"> clicking here</a>.</p>';

        if(!empty($default_access_token)){
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
            $response_err = 'Graph returned an error: ' . $e->getMessage();
            \Drupal::logger('simple_facebook_post')->warning($response_err);

            $markup .= '<p>Se produjo un error al intentar obtener información del usuario, 
            por favor compruebe los datos ingresados sean correctos e 
            intente iniciar sesion con Facebook.</p>
            <pre>@response_exception</pre>';
            
            $replace['@response_exception'] = $response_err;
          } catch(SDKException $e) {
            $sdk_err = 'Facebook SDK returned an error: ' . $e->getMessage();
            \Drupal::logger('simple_facebook_post')->warning($sdk_err);

            $markup .= '<p>Se produjo un error al intentar obtener información del usuario, 
            por favor compruebe los datos ingresados sean correctos e 
            intente iniciar sesion con Facebook.</p>
            <pre>@response_exception</pre>';
            
            $replace['@sdk_exception'] = $sdk_err;
          }
  
          if(!empty($user_name)){
            $replace['@user_name'] = $user_name;
            $markup .='<p>Currently, the system has access to @user_name facebook account.</p>';
          }
        } 

        return t($markup, $replace);
      }
      
    }
}
