<?php

namespace Drupal\catalog_importer\Feeds\Parser;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\Parser\ParserInterface;
use Drupal\feeds\Plugin\Type\PluginBase;
use Drupal\feeds\Result\FetcherResultInterface;
use Drupal\feeds\StateInterface;
use Drupal\catalog_importer\Feeds\Item\CatalogItem;
use Drupal\feeds\Result\ParserResult;

/**
 * Your Class Description.
 *
 * @FeedsParser(
 *   id = "marc_record_parser",
 *   title = @Translation("MARC Record Parser"),
 *   description = @Translation("Parser for catalog item records in MARC format")
 * )
 */
class MarcRecordParser extends PluginBase implements ParserInterface {

   /**
   * Hexadecimal value for Subfield indicator
   */
  private $subfield_indicator = "\x1F";

  /**
   * Hexadecimal value for End of Field
   */
  private $field_end = "\x1E";

  /**
   * Hexadecimal value for End of Record
   */
  private $record_end = "\x1D";

  // 00 = personal
  // 10 = corporate
  // 11 = conference
  // 20 = uncontrolled
  // 30 = Uniform Title
  // 40 
  private $author_fields = array('700', '710', '100', '110', '111', '711', '720', '264'); //'730','740','751', '752', '753', '754', 
  private $series_fields = array('440', '490', '810', '811', '830');
  private $title_fields = array('245', '130', '240', '246', '247', '440', '490', '500', '505', '700', '710', '711', '730', '740', '780', '800', '810', '811', '830', '840');
  private $subject_fields = array('600', '610', '611', '630', '648', '650', '651', '653', '654', '655', '656', '657', '658', '662', '690', '691', '692', '693', '694', '695', '696', '697', '698', '699');
  private $audience_fields = array('521','385');
  /**
   * {@inheritdoc}
   */
  public function parse(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
    $result = new ParserResult();
    $raw = $fetcher_result->getRaw();
    $marc_records = explode($this->record_end, $raw);

    foreach ($marc_records as $record) {
      if (!empty($record)) {
        $item = $this->parseItems($record);
        dd($item);
        $yourItem = new CatalogItem();
        // Process out the $item into an easily usable data set.
        $yourItem->set('guid', (string) $item['id']);
        $yourItem->set('tcn', (string) $item['id']);
        $yourItem->set('active_date', (string) $item['active_date']);
        $yourItem->set('title', $item['title']);
        
        // $yourItem->set('alt_titles', $titleArray);

        $yourItem->set('audience', $item['audience']);
        $yourItem->set('genre', $item['genre']);
        $yourItem->set('topics', $item['topics']);

        // $yourItem->set('type', $this->getItemKeywords($item, 'type'));
        // $yourItem->set('form', $this->getItemKeywords($item, 'form'));
        // $yourItem->set('classification', $this->getItemKeywords($item, 'ddc'));

        $yourItem->set('creators', $authors['names']);
        $yourItem->set('roles', $authors['roles']);
        $yourItem->set('item_creators', $authors['authors']);


        $identifiers = $this->processIdentifiers($item);
        $types = array_map('strval', array_values($identifiers['identifiers']));
        $yourItem->set('identifier_types', $types);
        $yourItem->set('item_ids', $identifiers['item_ids']);
        $ids = array_map('strval', array_keys($identifiers['identifiers']));
        $yourItem->set('identifier_ids', $ids);
        $yourItem->set('isbn', $identifiers['isbns']);

        $description = $this->getResourceDescription($item);
        $yourItem->set('description', $description);
      }
      $result->addItem($yourItem);
    }
    return $result;
  }
/**
   * Parse all of the items from the MARC record
   */
  protected function parseItems($marc_record) {
    $marc_record = $this->getMarcFields($marc_record);
    if(empty($marc_record['leader'])){
      return null;
    }
    $record = $this->mapMarcFields($marc_record);

    return $record;
  }
  private function getMarcFields($marc_record){
    $marc_field_values = explode($this->field_end, $marc_record);
  
    // Now this is harder, we need to break the leader from the directory
    $start_length = strlen($marc_field_values[0]);
    $leader = substr($marc_field_values[0], 0, 23);
    if(!$leader || empty($leader)){
      return null;
    }
    $directory = substr($marc_field_values[0], 24, $start_length);
    $marc_field_values[0] = $leader;

    // Get the field numbers from the directory
    // $directoryfields contaings the fieldname, start position and length
    // that will get taken care of in a second
    $directory_fields = str_split($directory, 12);

    //Start building $record array
    $record = array();
    //First we set the leader and take the leader value out of the values
    $record['leader'] = $leader;
    array_splice($marc_field_values, 0, 1);

    $marc_field_count = array();
    //Then we loop through the directory fields and build array
    foreach ($directory_fields AS $key => $directory_field) {
      
      //The marc field number is just the first 3 characters
      $field_number = substr($directory_field, 0, 3);
      //The field value is the correpsonding value in the marcfieldvalues array.
      $field_value = $marc_field_values[$key];
      
      //We need to keep track of the field iterations
      if (!isset($marc_field_count[$field_number]) || !$marc_field_count[$field_number]){
        $marc_field_count[$field_number] = 0;
      }
      $field_count = $marc_field_count[$field_number];
      if (substr($directory_field, 0, 2) == '00') {
        // Populate Control fields
        // Technically control fields can be repeated, not that I ever see it.
        $record[$field_number][$field_count] = $field_value;
      }
      else {
        //Start work on subfields
        // US (char) 31(dec) 1F 037 - character used to seperate subfields
        $subfields = explode($this->subfield_indicator, $marc_field_values[$key]);
        
        //Get rid of indicators
        array_splice($subfields, 0, 1);

        //Insert subfields in database
        foreach ($subfields as $subfield) {
          $subfield_code = substr($subfield, 0, 1);
          $subfield_value = substr($subfield, 1, (strlen($subfield)-1));
          $record[$field_number][$field_count][$subfield_code] = $subfield_value;
        }
      }
      //We need to keep track of the field iterations
      $marc_field_count[$field_number]++;
    }
    return $record;
  }

  private function mapMarcFields($marc_record){
    dd($marc_record);
    $record = array(
      'creators' => array(), 
      'description' => array(),
      'urls' => '', // 856[0][u]
      'cover' => '',
      'topics' => array(), // 650
      'genre' => array(), // 655
      'audience' => array(),
      'titles' => array(),
    );
    $catalog_item = new CatalogItem();

    foreach($marc_record as $field => &$info){
      switch($field){
        case '001':
          $catalog_item->set('guid', (string) $info[0]);
          $catalog_item->set('tcn', (string) $info[0]); break;
        case '005': 
          $catalog_item->set('active_date', substr($info[0], 0, 8)); break;
        case '008':
          $lang = substr($info[0], 30);
          $lang = trim($lang);
          if(substr($lang,2,3) !== 'eng' && substr($lang,2,3) !== 'und' && substr($lang,2,3) !== 'zxx'){        
            $record['genre'][] = 'foreign language';
          } break;
        case '028':
          if(strtolower($info[0]['b']) == 'kanopy'){
            $record['cover'] = 'https://www.kanopy.com/sites/default/files/imagecache/vp_poster_small/video-assets/' . $info[0]['a'] . '_poster.jpg'; break;
          }
        case '856':
          foreach($info as $url){
            if(empty($cover) && strpos($url['u'], '/external-image') !== FALSE){
              $record['cover'] = $url['u'];
            }
            $record['urls'][] = $url['u'];
          } break;
        case '546':
          $info[0]['a'] = implode(", ", explode(",",$info[0]['a']));
        case '520':
          $record['description'][] = $this->getFieldArray($info); break;
        default:
          $k = ltrim($key, '0');
          if($k >= 100 && $k !== 'leader' && ($k < 300 || $k >= 400) && !in_array($k, $this->subject_fields)){
            $record['description'][] = implode("; ", $this->getFieldArray($info, " "));
          }
          if(in_array($key, $this->author_fields)){
            $k = substr($key, -2);
            switch($k){
              case '00':
                foreach($info as $i => $val){
                  $record['creators']['names'][] = isset($val['a']) ? $val['a'] : isset($val['q']) ? $val['q'] : (string) $val;
                  $record['creators']['roles'][] = isset($val['b']) ? $val['b'] : isset($val['c']) ? $val['c'] : 'creator';
                } break;
              case '10':
                foreach($info as $i => $val){
                  $record['creators']['names'][] = isset($val['a']) ? $val['a'] : isset($val['b']) ? $val['b'] : (string) $val;
                  $record['creators']['roles'][] = isset($val['e']) ? $val['e'] : isset($val[4]) ? $val[4] : 'creator';
                } break;
              case '11':
                foreach($info as $i => $val){
                  $record['creators']['names'][] = isset($val['a']) ? $val['a'] : isset($val['e']) ? $val['e'] : (string) $val;
                  $record['creators']['roles'][] = isset($val['j']) ? $val['j'] : isset($val['i']) ? $val['i'] : isset($val[4]) ? $val[4] : 'creator';
                } break;
              case '20':
                foreach($info as $i => $val){
                  $record['creators']['names'][] = isset($val['a']) ? $val['a'] : (string) $val;
                  $record['creators']['roles'][] = isset($val['e']) ? $val['e'] : isset($val[4]) ? $val[4] : isset($val[6]) ? $val[6] : 'creator';
                } break;
              default:
                foreach($info as $i => $val){
                  $record['creators']['names'][] = isset($val['b']) && strpos(strtolower($val['b']), 'great courses') !== FALSE ? "The Great Courses" : isset($val['b']) ? $val['b'] : (string) $val;
                  $record['creators']['roles'][] = isset($val['e']) ? $val['e'] : isset($val[4]) ? $val[4] : isset($val[6]) ? $val[6] : 'production/distribution';
                }
            }
          }
          if(in_array($key, $this->title_fields)){
            foreach($info as $k => $v){
              $title = $this->getStringField($v, $key);
              if(!empty($title)){
                if(empty($record['titles'])){
                  $catalog_item->set('title', $title);
                } else {
                  $record['titles'][]=$title;
                }
              }
            }
          }
          if(in_array($key,$this->audience_fields)){
            $s = $this->getFieldArray($info, null, true);
            if(!empty($s)){
              $record['audience'] = array_merge($record['audience'], $s);
            }
          }
          if(in_array($key,$this->subject_fields)){
            $s = $this->getFieldArray($info, null, true);
            if(!empty($s)){
              $record['topics'] = array_merge($record['topics'], $s);
              $record['audience'] = array_merge($record['audience'], $s);
              if($subject_field == 655){
                $record['genre'] = $s;
              }
            }
          }
      }
    }



    $record = array(
      'creators' => array(), 
      'description' => array(),
      'urls' => '', // 856[0][u]
      'cover' => '',
      'topics' => array(), // 650
      'genre' => array(), // 655
      'audience' => array(),
      'titles' => array(),
    );
    $catalog_item->set('alt_titles', $record['titles']);
    $catalog_item->set('audience', $record['audience']);
    $catalog_item->set('genre', $record['genre']);
    $catalog_item->set('topics', $record['topics']);
    // $catalog_item->set('type', $this->getItemKeywords($item, 'type'));
    // $catalog_item->set('form', $this->getItemKeywords($item, 'form'));
    // $catalog_item->set('classification', $this->getItemKeywords($item, 'ddc'));
    $catalog_item->set('creators', $record['creators']['names']);
    $catalog_item->set('roles', $record['creators']['roles']);

    return $record;
  }
  private function getStringField($array, $field_number, $separator = " "){
    $field_number = strval($field_number);
    $field = array_merge(array(), $array);
    if(empty($field)){
      return null;
    }

    if(isset($field->i1)){
      unset($field->i1);
    }
    if(isset($field->i2)){
      unset($field->i2);
    }
    
    $value='';

    if($field_number == 700){
      $value = $field['a'];
    }elseif($field_number == 710){
      $value = isset($field['a']) ? $field['a'] : implode($separator, $field);
    }elseif($field_number == 264){
      $value = isset($field['b']) ? $field['b']: implode($separator, $field);
    }elseif($field_number == 245){
      $value = isset($field['a']) ? $field['a'] : implode($separator, $field);
    }else{
      $value = implode($separator, $field);
    }
    $value = trim($value);
    return rtrim($value, ",;.:");
  }

  private function getFieldArray($array, $separator = null, $strip_numeric = false){
    $field = array_merge(array(), $array);
    $value = array();

    if(isset($field->i1)){
      unset($field->i1);
    }
    if(isset($field->i2)){
      unset($field->i2);
    }

    foreach($field as $f){
      if(is_array($f)){
        if(isset($field->i1)){
          unset($field->i1);
        }
        if(isset($field->i2)){
          unset($field->i2);
        }
        if($separator){
          $val = implode($separator, $f);
          $value[]=$val;
        } else if(!$strip_numeric){
          $value = array_merge($value, $f);
        } else {
          foreach($f as $k=>$v){
            if(!is_numeric($k)){
              $value[]=$v;
            }
          }
        }
      } else {
        $value[] = $f;
      }
    }
    return $value;
  }
  /**
   * {@inheritdoc}
   */
  public function getMappingSources() {
    return [
      'guid' => [
        'label' => $this->t('GUID'),
        'description' => $this->t('Unique ID for Feeds.'),
      ],
      'title' => [
        'label' => $this->t('Title'),
        'description' => $this->t('Resource Title'),
      ],
      'active_date' => [
        'label' => $this->t('Active Date'),
        'description' => $this->t('Date item added to catalog.'),
      ],
      'alt_titles' => [
        'label' => $this->t('Alternative Titles'),
        'description' => $this->t('Other titles for this item.'),
      ],
      'audience' => [
        'label' => $this->t('Audience'),
        'description' => $this->t('Audience indicators'),
      ],
      'creators' => [
        'label' => $this->t('Creators'),
        'description' => $this->t('Resource creators/authors'),
      ],
      'roles' => [
        'label' => $this->t('Roles'),
        'description' => $this->t('Resource creators/authors roles'),
      ],
      'description' => [
        'label' => $this->t('Description'),
        'description' => $this->t('Resource abstract/description'),
      ],
      'item_creators'=> [
        'label' => $this->t('ITEM CREATORS'),
        'description' => $this->t('Resource creators and their roles'),
      ],
      'item_ids'=> [
        'label' => $this->t('ITEM IDs'),
        'description' => $this->t('Resource isbns and identifiers'),
      ],
      'featured_collection' => [
        'label' => $this->t('Featured Collection'),
        'description' => $this->t('Featured Collection Terms'),
      ],
      'genre' => [
        'label' => $this->t('Genre'),
        'description' => $this->t('Genre Indicators'),
      ],
      'isbn' => [
        'label' => $this->t('ISBN'),
        'description' => $this->t('ISBN'),
      ],
      'topics' => [
        'label' => $this->t('Topics'),
        'description' => $this->t('Topic/Subject Indicators'),
      ],
      'image' => [
        'label' => $this->t('Image'),
        'description' => $this->t('url for resource image'),
      ],
      'tcn' => [
        'label' => $this->t('TCN'),
        'description' => $this->t('Record ID'),
      ],
      'url' => [
        'label' => $this->t('URL'),
        'description' => $this->t('item Record URL'),
      ],
      'identifier_ids' => [
        'label' => $this->t('Identifier IDs'),
        'description' => $this->t('item Record identifier IDs'),
      ],
      'identifier_types' => [
        'label' => $this->t('Identifier Types'),
        'description' => $this->t('item Record identifier types'),
      ],
      'catalog' => [
        'label' => $this->t('Catalog'),
        'description' => $this->t('Catalog source for this resource.'),
      ],
      'type' => [
        'label' => $this->t('Resource Type'),
        'description' => $this->t('typeOfResouce'),
      ],
      'form' => [
        'label' => $this->t('Form'),
        'description' => $this->t('Item form.'),
      ],
      'classification' => [
        'label' => $this->t('Dewey'),
        'description' => $this->t('DDC'),
      ],
    ];
  }
}