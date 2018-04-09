<?php

namespace Drupal\calibr8_photoswipe\Controller;

use Drupal\Core\Controller\ControllerBase;
use \Symfony\Component\HttpFoundation\Response;

/**
 * An example controller.
 */
class PswpElement extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function Render() {
    $build = array(
      '#theme' => 'calibr8_photoswipe_pswp',
    );
    // This is the important part, because will render only the TWIG template.
    return new Response(render($build));
  }

}