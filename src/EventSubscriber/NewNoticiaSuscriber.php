<?php
namespace Drupal\analisis_autopost\EventSubscriber;

use Drupal\entity_events\Event\EntityEvent;
use Drupal\entity_events\EventSubscriber\EntityEventInsertSubscriber;
use \Drupal\Component\Utility\Html;
use Facebook\Facebook;
use Drupal\analisis_autopost\Post\Post;
use Drupal\analisis_autopost\Facebook\FacebookPost;
use Drupal\analisis_autopost\Twitter\TwitterPost;
use Drupal\Core\Render\Element\Weight;

class NewNoticiaSuscriber extends EntityEventInsertSubscriber {

  public function onEntityInsert(EntityEvent $event) {

    $entity = $event->getEntity();
    if ($entity instanceof \Drupal\node\NodeInterface) {
      /** @var \Drupal\node\NodeInterface $node  */
      $node = $entity;
    } else {
      return;
    }
    
    if($node->bundle() === 'noticia' && isset($node->publicar_en_facebook)){

  
      if(isset($node->field_facebook_post_id)){
        $post_id  = $node->field_facebook_post_id->getValue();
        if(!empty($post_id[0]['value'])){
          return;
        }
      } else {
        return;
      }

      // Comprobamos que la opcion compartie en facebook esta disponible en la entidad 
      $publish_option = $node->publicar_en_facebook->getValue();
      $publish_option = (!empty($publish_option)) ? $publish_option[0]['value'] : 0;
      // sin la opcion de publicar en rs no se puede publicar
      if(empty($publish_option)) return;

      // Empesamos con el proceso de publicación.
      // Primero tenemos que armar el objeto post 
      $post = new Post();
      // Le pasamos la entidad, post tiene que saber que datos usar de la entidad
      $post->setUpFromEntity($node);
      // Vemos si se tiene lo minimo necesario para el posteo, si no se da el caso, salimos
      if(!$post->isPosteable()){
        return;
      }
      // Cargamos los manejadores de posteo para fb y tw
      $fbp = new FacebookPost(); /** @todo Esto tendria que ser un servicio */
      //$twp = new TwitterPost();

      $id_post_fb = $fbp->postearFacil($post);
      //$id_post_tw = $twp->postearFacil($post);

      if(!empty($id_post_fb)){
        $node->set('field_facebook_post_id', $id_post_fb);
      }

    }
  }

}