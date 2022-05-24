<?php 
namespace Drupal\analisis_autopost\Twitter;

use \Drupal\analisis_autopost\Post\Post;
use \Abraham\TwitterOAuth\TwitterOAuth; 
use Exception;

class TwitterPost {
//CONSUMER_KEY, CONSUMER_SECRET, $access_token, $access_token_secret)
  private $consumer_key;
  private $consumer_secret;
  private $access_token;
  private $access_token_secret;
  /** @var \Abraham\TwitterOAuth\TwitterOAuth $twitter */
  private $twitter;

  /**
   *  @method   TwitterPost() Constructor de la clase
   *  @param void
   *  @return void
   */
  public function __construct()
  {
    
    $this->consumer_key         = \Drupal::state()->get('analisis_autopost.twitter_consumer_key');
    $this->consumer_secret      = \Drupal::state()->get('analisis_autopost.twitter_consumer_secret');
    $this->access_token         = \Drupal::state()->get('analisis_autopost.twitter_access_token');
    $this->access_token_secret  = \Drupal::state()->get('analisis_autopost.twitter_access_token_secret');

  }

  public function isSetUp(){



    if(!empty($this->consumer_key) || !empty($this->consumer_secret))
    {
      $this->twitter = new twitteroauth($this->consumer_key, $this->consumer_secret);
      if(!empty($this->access_token) && !empty($this->access_token_secret))
      {
        $this->twitter->setOauthToken($this->access_token, $this->access_token_secret);
        $content = $this->twitter->get("account/verify_credentials");
        
        return (empty($content)) ? -1 : 1;
      }
      else 
      {
        return 0;
      }
    } 
    else 
    {
      return 0;
    }
  }

  /**
   * Metodo encargado del proceso de  twittear
   *
   * @param Post $post La noticia en si, los campos que se usan para armar el posteo y el link
   * @return void
   */
  public function postearFacil(Post $post){

    // Create and post manager instances.
    /** @var Abraham\TwitterOAuth\TwitterOAuth $client */

  }

}
