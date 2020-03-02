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


  private $author_fields = array('700', '710', '100', '110', '111', '711', '720', '264'); //'730','740','751', '752', '753', '754', 
  private $series_fields = array('440', '490', '810', '811', '830');
  private $title_fields = array('245', '130', '210', '222', '240', '242', '243', '246', '247', '730', '740'); //'240', '246', '247', '440', '490', '500', '505', '700', '710', '711', '730', '740', '780', '800', '810', '811', '830', '840'
  private $subject_fields = array('600', '610', '611', '630', '648', '650', '651', '653', '654', '655', '656', '657', '658', '662', '690', '691', '692', '693', '694', '695', '696', '697', '698', '699');
  private $audience_fields = array('521','385');

  private $form_codes = array(
    'a' => 'map',
    'c' =>'electronic resource',
    'd' => 'globe',
    'f' => 'tactile material',
    'g' =>'projected graphic',
    'h' => 'microform',
    'k' => 'nonprojected graphic',
    'm' => 'motion picture',
    'o' => 'kit',
    'q' => 'notated music',
    'r' => 'remote-sensing image',
    's' => 'sound recording',
    't' => 'text',
    'v' => 'videorecording',
    'z' => 'unspecified'
  );
  private $resource_types = array(
    'a' =>'text',
    't' =>'text',
    'e' =>'cartographic',
    'f' =>'cartographic',
    'c' =>'notated music',
    'd' =>'notated music',
    'i' =>'sound recording-nonmusical',
    'j' =>'sound recording-musical',
    'k' =>'still image',
    'g' =>'moving image',
    'r' =>'three dimensional object',
    'm' =>'software, multimedia',
    'p' =>'mixed material'
  );
  /**
   * {@inheritdoc}
   */
  public function parse(FeedInterface $feed, FetcherResultInterface $fetcher_result, StateInterface $state) {
    $result = new ParserResult();
    $raw = $fetcher_result->getRaw();
    $marc_records = explode($this->record_end, $raw);

    foreach ($marc_records as $record) {
      $marc_record = $this->getMarcFields($record);

      if(empty($marc_record['leader'])){
        continue;
      }
      \Drupal::logger('catalog_importer')->notice('RECORD: <pre>@exclude</pre>', array(
        '@exclude'  => print_r($marc_record, TRUE),
      )); 
      $item = $this->mapMarcFields($marc_record);
      $result->addItem($item);
    }

    return $result;
  }
/**
   * Parse all of the items from the MARC record
   */
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
    //dd($marc_record);
    $record = array(
      'creators'        => [
        'names'=>array(),
        'roles'=>array()
      ], 
      'description'     => array(),
      'urls'            => array(), // 856[0][u]
      'cover'           => '',
      'topics'          => array(), // 650
      'genre'           => array(), // 655
      'audience'        => array(),
      'titles'          => array(),
      'title'           => '',
      'type'            => '',
      'form'            => array(),
      'classification'  => array(),
      'identifier_ids' => array(),
      'identifier_types'=> array(),
      'isbn'            =>array(),
    );
    $catalog_item = new CatalogItem();

    foreach($marc_record as $field => &$info){
      switch($field){
        case 'leader':
          $type = substr($info, 6, 1);
          if(isset($this->resource_types[$type])){
            $catalog_item->set('type', $this->resource_types[$type]);
          } break;
        case '001':
          $catalog_item->set('guid', (string) $info[0]);
          $catalog_item->set('tcn', (string) $info[0]); break;
        case '005': 
          $catalog_item->set('active_date', substr($info[0], 0, 8)); break;
        case '007':
          foreach($info as $v){
            $form = substr($v, 0, 1);
            if(isset($this->form_codes[$form])){
              $record['form'][]=$this->form_codes[$form];
            }
          } break;
        case '008':
          $lang = substr($info[0], 30);
          $lang = trim($lang);
          if(substr($lang,2,3) !== 'eng' && substr($lang,2,3) !== 'und' && substr($lang,2,3) !== 'zxx'){        
            $record['genre'][] = 'foreign language';
          } break;
        case '024':
          foreach($info as $i => $v){
            if(isset($v['i1'])){
              // http://www.loc.gov/marc/bibliographic/bd024.html
              switch($v['i1']){
                case 0:
                case 1:
                case 2:
                case 3:
                case 4:
                case 7:
                default:
              }

            }

          }

        case '028':
          if(strtolower($info[0]['b']) == 'kanopy'){
            $record['cover'] = 'https://www.kanopy.com/sites/default/files/imagecache/vp_poster_small/video-assets/' . $info[0]['a'] . '_poster.jpg'; break;
          }
        case '082':
          foreach($info as $classification){
            if(isset($classification['a'])){
              $record['classification'][] = $classification['a'];
            }
          } break;
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
          //$record['description'][] = $this->getFieldArray($info); break;
          array_unshift($record['description'], implode(';', $this->getFieldArray($info))); break;
        default:
          $k = ltrim($field, '0');
          if($k >= 100 && $field !== 'leader' && ($k < 300 || $k >= 400) && !in_array($k, $this->subject_fields)){
            $record['description'][] = implode("; ", $this->getFieldArray($info, " "));
          }
         
          if(in_array($field, $this->author_fields)){
            // \Drupal::logger('catalog_importer')->notice("CREATOR- $field: <pre>@exclude</pre>", array(
            //   '@exclude'  => print_r($info, TRUE),
            // )); 
            $record['creators'] = $this->getCreators($field, $info, $record['creators']);
          }
          if(in_array($field, $this->title_fields)){
            foreach($info as $k => $v){
              $title = $this->getStringField($v, $field);
              if(!empty($title)){
                if(empty($record['title']) && in_array($field, ['245', '130'])){
                  $record['title'] = $title;
                } else {
                  $record['titles'][]=$title;
                }
              }
            }
          }
          if(in_array($field,$this->audience_fields)){
            $s = $this->getFieldArray($info, null, true);
            if(!empty($s)){
              $record['audience'] = array_merge($record['audience'], $s);
            }
          }
          if(in_array($field,$this->subject_fields)){
            $s = $this->getFieldArray($info, null, true);
            if(!empty($s)){
              $record['topics'] = array_merge($record['topics'], $s);
              $record['audience'] = array_merge($record['audience'], $s);
              if($field == 655){
                $record['genre'] = array_merge($record['genre'], $s);
              }
            }
          }
      }
    }

    $roles = array_map(function($role_array){
      return implode(", ", $role_array);
    }, $record['creators']['roles']);

    $catalog_item->set('alt_titles', $record['titles'])
      ->set('audience', $record['audience'])
      ->set('genre', $record['genre'])
      ->set('topics', $record['topics'])
      ->set('creators', $record['creators']['names'])
      ->set('roles', $roles)
      ->set('image', $record['cover'])
      ->set('form', $record['form'])
      ->set('classification', $record['classification'])
      ->set('description', implode("<br/><br/>", array_filter($record['description'])));
      //->set('roles', $record['creators']['roles'])
    if(empty($record['title'])){
      $catalog_item->set('title', $record['titles'][0]);
    } else {
      $catalog_item->set('title', $record['title']);
    }

    \Drupal::logger('catalog_importer')->notice('ITEM: <pre>@exclude</pre>', array(
      '@exclude'  => print_r($catalog_item, TRUE),
    )); 
    return $catalog_item;
  }
  /**
   * Function to retrieve the creator fields from a record
   */
  private function getCreators($field, $info, &$creators){
    $k = substr($field, -2);

    foreach($info as $i => $val){
      $name = '';
      $roles = array();
      foreach($val as $indicator => $v){
        switch($k){
          case '10':
            if($indicator == 'a'){
              $name = $v; break;
            }
            if($indicator == 'b' && empty($name)){
              $name = $v; break;
            }
            if(in_array($indicator, [4, 'e'])){
              $roles[]=trim($v, ",;.: "); break;
            } break;
          case '11':
            if($indicator == 'a'){
              $name = $v; break;
            }
            if($indicator == 'e' && empty($name)){
              $name = $v; break;
            }
            if(in_array($indicator, [4, 'j', 'i'])){
              $roles[]=trim($v, ",;.: "); break;
            } break;
          case '20':
            if($indicator == 'a'){
              $name = $v; break;
            }
            if($indicator == 'e' && empty($name)){
              $name = $v; break;
            }
            if(in_array($indicator, [4, 'b', 'e', 'c', 6])){
              $roles[]= trim($v, ",;.: "); break;
            } break;
          case '64':
            if($indicator == 'b'){
              if(strpos(strtolower($val['b']), 'great courses') !== FALSE){
                $name ="The Great Courses"; break;
              } else {
                $name = $v; break;
              }
            }
            if(in_array($indicator, [4, 'e', 6])){
              $roles[]=trim($v, ",;.: "); break;
            } break;
          default:
            if($indicator == 'a'){
              $name = $v; break;
            }
            if($indicator == 'q' && empty($name)){
              $name = $v; break;
            }
            if(in_array($indicator, ['b', 'e','c', 4, 6])){
              $roles[]=trim($v, ",;.: ");
            }
        }
      }

      $name = trim(preg_replace('/\(.*\)/',"",$name),"= / . : , ; ");
      $name = implode(", ", array_map(function($n){
        return preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u','',$n);
      }, explode(",", $name)));

      $roles = array_unique(array_map('strtolower', $roles));

      if(!empty($name)){
        if(empty($roles)){
          $roles = $k == 64 ? ['prod/dst'] : ['creator'];
        }
        if(!in_array($name, $creators['names'])){
          if($k == '00'){
            array_unshift($creators['names'], $name);
            array_unshift($creators['roles'], $roles);
          } else {
          $creators['names'][] = $name;
          $creators['roles'][] = $roles;
          }
        } else {
          $key = array_search($name, $creators['names']);
          foreach($roles as $r){
            if(!in_array($r, $creators['roles'][$key])){
              $creators['roles'][$key][]=$r;
            }
          }
        }
      }
    }
    return $creators;
  }

  private function getStringField($array, $field_number, $separator = " ", $subfield=NULL){
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

    if($subfield){
      $value = (string) $field[$subfield];
    }elseif($field_number == 700){
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