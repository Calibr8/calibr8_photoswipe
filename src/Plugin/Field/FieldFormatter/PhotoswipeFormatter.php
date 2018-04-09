<?php

/**
 * @file
 * Contains \Drupal\calibr8_ds\Plugin\Field\FieldFormatter\TitleElementFormatter.
 */

namespace Drupal\calibr8_photoswipe\Plugin\Field\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Template\Attribute;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Database\Database;
use Drupal\Core\Image;

/**
 * Plugin implementation of the 'photoswipe' formatter.
 *
 * @FieldFormatter(
 *   id = "photoswipe",
 *   label = @Translation("Photoswipe"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class PhotoswipeFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();

    // Image styles
    $image_styles = image_style_options(TRUE);
    $image_styles['none'] = t('None (original image)');

    $form['thumbnail_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Thumbnail style'),
      '#options' => $image_styles,
      '#default_value' => $settings['thumbnail_style'],
    ];
    $form['slide_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Slide style'),
      '#options' => $image_styles,
      '#default_value' => $settings['slide_style'],
    ];
    $form['isotope'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show in isotope container'),
      '#default_value' => $settings['isotope'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [
      $this->t('Photoswipe gallery'),
    ];
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'thumbnail_style' => 'thumbnail',
      'slide_style' => '',
      'isotope' => false,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $thumbnails = [];
    $thumbnail_style = $this->getSetting('thumbnail_style');
    $slide_style = $this->getSetting('slide_style');

    $entity = $items->getEntity();
    $img_data = $this->getImageData($entity->id(), $this->fieldDefinition->getName(),  $entity->getEntityTypeId());

    // generate gallery items
    foreach ($items as $delta => $item) {
      if (!$item->entity) {
        continue;
      }
      $entity = $item->entity;
      $uri = $entity->getFileUri();

      // thumbnail image
      $thumbnail = [
        '#theme' => 'image_style',
        '#style_name' => $thumbnail_style,
        '#uri' => $uri,
      ];

      // slide variables
      if($slide_style == 'none') {
        $path = file_create_url($uri);
      } else {
        $slide = ImageStyle::load($slide_style);
        $path = $slide->buildUrl($uri);
      }
      // transform path if https
      if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) {
        $path = str_replace('http://', 'https://', $path);
      }
      // size
      $size = $this->getImageStyleSize($path);

      // caption
      $caption = '';

      if (isset($img_data[$entity->id()])) {
        $caption = $img_data[$entity->id()]['title'];
      }

      // Add item to thumbnail list
      $thumbnails[$delta] = [
        '#theme' => 'calibr8_photoswipe_item',
        '#delta' => $delta,
        '#item' => $item,
        '#thumbnail' => $thumbnail,
        '#slide_path' => $path,
        '#slide_size' => $size,
        '#caption' => $caption
      ];
    }

    // Create render array
    $element[0] = [
      '#theme' => 'calibr8_photoswipe_wrapper',
      '#items' => $thumbnails,
      '#attributes' => new Attribute(['class' => []]),
    ];

    // Add libraries
    $element['#attached']['library'][] = 'calibr8_photoswipe/photoswipe';
    $element['#attached']['library'][] = 'calibr8_photoswipe/photoswipe_init';

    if($this->getSetting('isotope')) {
      $element['#attached']['library'][] = 'calibr8_photoswipe/isotope';
      $element[0]['#attributes']->addClass('photoswipe-gallery-isotope');
    }

    return $element;
  }

  function getImageData($id, $img_field, $entity_type = 'node') {
    $connection = Database::getConnection();

    $data = [];

    $res = $connection->select($entity_type . '__' . $img_field, 'imgtable')
    ->fields('imgtable',[
        $img_field . '_target_id',
        $img_field . '_alt',
        $img_field . '_title'
      ])
    ->condition('imgtable.entity_id', $id)->execute()->fetchAll();

    foreach($res as $row){
      $data[$row->{$img_field . '_target_id'}] = [
        'title' => $row->{$img_field . '_title'},
        'alt' => $row->{$img_field . '_alt'}
      ];
    }
    return $data;
  }

  //Get image size, either via getimagesize or a faster aproach
  function getImageStyleSize($path) {

    // [C] no size
    return '0x0';

    //[A] getiamgesize
    $imagesize = getimagesize($path);
    return $imagesize[0] . 'x' . $imagesize[1];

    //[B] If get Imagesize is too slow, use this function: faster but might return some notices
    $headers = array(
      "Range: bytes=0-32768"
    );

    $curl = curl_init($path);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($curl);
    curl_close($curl);

    $im = imagecreatefromstring($data);

    $width = imagesx($im);
    $height = imagesy($im);

    return $width . 'x' . $height;
  }
}
