<?php
namespace Drupal\analisis_autopost\Config;

class ConfigManager{

  public function __construct()
  { 
    
  }
  /**
   * Obtener toda la configuración
   */
  public static function getAll(array $config)
  {
    $fb_config = self::getFacebookConfig();
    $tw_config = self::getTwitterConfig();
    $result = $fb_config;
    foreach($tw_config as $key => $value){
      $result[$key] = $value;
    }
    return $result;
  }
  /**
   * Guardar toda la configuracion
   */
  public static function setAll(array $config){
    self::setContentConfig($config);
    self::setFacebookConfig($config);
    self::setTwitterConfig($config);
  }

  public static function setContentConfig(array $config){
      \Drupal::state()->setMultiple([
        'analisis_autopost.content' => $config['content'] ?? '',
        'analisis_autopost.title'   => $config['title'] ?? '',
        'analisis_autopost.title_suffix'      => $config['title_suffix'] ?? '',
        'analisis_autopost.body'  => $config['body'] ?? '',
        'analisis_autopost.media' => $config['media'] ?? '',
      ]);
  
  }
  public static function getContentConfig(){
  
    return [
      'content'       => \Drupal::state()->get('analisis_autopost.content', ''),
      'title'         => \Drupal::state()->get('analisis_autopost.title', ''),
      'title_suffix'  => \Drupal::state()->get('analisis_autopost.title_suffix', ''),
      'body'          => \Drupal::state()->get('analisis_autopost.body', ''),
      'media'         => \Drupal::state()->get('analisis_autopost.media', ''),
    ];

}
  public static function getFacebookConfig(){
    return [
      'facebook_app_id'       => \Drupal::state()->get('analisis_autopost.facebook_app_id', ''),
      'facebook_app_secret'   => \Drupal::state()->get('analisis_autopost.facebook_app_secret', ''),
      'facebook_page_id'      => \Drupal::state()->get('analisis_autopost.facebook_page_id', ''),
      'facebook_api_version'  => \Drupal::state()->get('analisis_autopost.facebook_api_version', ''),
      'facebook_permisos'     => \Drupal::state()->get('analisis_autopost.facebook_permisos', '')
    ];
  }
  /**
   * Guardar la configuración para facebook
   */
  public static function setFacebookConfig(array $config){
      \Drupal::state()->setMultiple([
        'analisis_autopost.facebook_app_id'       => $config['facebook_app_id'] ?? '',
        'analisis_autopost.facebook_app_secret'   => $config['facebook_app_secret'] ?? '',
        'analisis_autopost.facebook_page_id'      => $config['facebook_page_id'] ?? '',
        'analisis_autopost.facebook_api_version'  => $config['facebook_api_version'] ?? '',
        'analisis_autopost.facebook_permisos'     => $config['facebook_permisos'] ?? '',
      ]);
  }
  /**
   * Obtener la configuración
   */
  public static function getTwitterConfig(){
    return [
      'twitter_consumer_key'        => \Drupal::state()->get('analisis_autopost.twitter_consumer_key', ''),
      'twitter_consumer_secret'     => \Drupal::state()->get('analisis_autopost.twitter_consumer_secret', ''),
      'twitter_access_token'        => \Drupal::state()->get('analisis_autopost.twitter_access_token', ''),
      'twitter_access_token_secret' => \Drupal::state()->get('analisis_autopost.twitter_access_token_secret', '')
    ];
  }
  /**
   * Guardar la configuracion para twitter
   */
  public static function setTwitterConfig(array $config){
    if(empty($config)){
      return;
    }

    \Drupal::state()->setMultiple([
      'analisis_autopost.twitter_consumer_key'        => $config['twitter_consumer_key'] ?? '',
      'analisis_autopost.twitter_consumer_secret'     => $config['twitter_consumer_secret'] ?? '',
      'analisis_autopost.twitter_access_token'        => $config['twitter_access_token'] ?? '',
      'analisis_autopost.twitter_access_token_secret' => $config['twitter_access_token_secret'] ?? ''
    ]);

  }

  public static function getFbUserAccessToken(){
    return \Drupal::state()->get('analisis_autopost.user_acces_token', null);
  }
  public static function setFbUserAccessToken($token){
    return \Drupal::state()->set('analisis_autopost.user_acces_token', $token);
  }

  public static function isFbConfigured(){
    $fb_config = self::getFacebookConfig();
    foreach($fb_config as $key => $value){
      if(empty($value)){
        return false;
      }
    }
    return true;
  }
}
