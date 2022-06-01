<?php

namespace Drupal\simple_facebook_post\Controller;
use Drupal\simple_facebook_post\Facebook\FacebookPost;
use Symfony\Component\HttpFoundation\Response;

class AnalisisAutopostController extends Generic{


    public function getFacebookCallback(){
  

      $fb     = new FacebookPost();
      $state  = null;
      if (isset($_GET['state'])) {
        $state = $_GET['state'];
      }
      
      $result =  $fb->getAndSaveTokenFromCallback($state);

      $resp = new  Response('ok ' . $result, 200);
      return $resp;

   }

}

