<?php

namespace Drupal\drupal_helpers;

/**
 * Class Random.
 *
 * @package Drupal\drupal_helpers
 */
class Random {

  protected static $loremText = <<<EOT
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam iaculis, velit gravida convallis tincidunt, felis enim venenatis lorem, nec lobortis nisl urna et mi. Pellentesque ac dictum ante. Fusce dignissim tempor elementum. Ut dignissim convallis eros, viverra luctus lacus consequat ac. Sed feugiat velit sed magna aliquam accumsan. Nam vitae porta tortor. Nam auctor dui a neque iaculis in aliquam erat viverra. Duis orci nunc, lacinia in malesuada et, euismod id turpis. Cras mattis vulputate erat, eget tempor magna egestas eu. Vestibulum sit amet massa est.
Vivamus pretium placerat lorem, in tempor massa convallis sit amet. Aliquam sed quam eget ligula luctus aliquam sed vitae nulla. Aliquam dui dolor, ullamcorper eget rutrum ut, hendrerit ac lorem. Donec magna est, sollicitudin vel ultrices vel, mattis ut odio. Integer vel felis laoreet purus sollicitudin varius sed id ipsum. Suspendisse potenti. Praesent ut justo vitae metus luctus vehicula a et purus. Suspendisse potenti. Sed viverra, quam non hendrerit laoreet, massa odio blandit arcu, ac molestie metus diam eu tortor. Donec erat arcu, ultrices sit amet placerat non, feugiat in arcu. Mauris eros quam, varius eget volutpat vel, tristique sed est. In faucibus feugiat urna sit amet elementum. Integer consequat rhoncus libero, in molestie augue posuere et. Phasellus ac eleifend magna. Proin vulputate dui ac justo pharetra consequat. In vel iaculis ligula.
Cras vestibulum lacus sit amet sem commodo ullamcorper aliquet eros vestibulum. Sed fermentum nulla quis risus suscipit dapibus. Sed vitae velit ut dolor varius semper at id lectus. Aenean quis leo sit amet tellus tempus cursus. Vivamus semper vehicula ante eget semper. In ac ipsum erat. Suspendisse lectus erat, commodo nec fringilla quis, interdum non leo. Vivamus et lectus vitae risus porta sollicitudin luctus eget est. Etiam quis elit vel est suscipit tristique. Nullam fringilla purus ac velit gravida ullamcorper. Praesent porttitor ante non lacus suscipit porta. Nunc fermentum sem et metus aliquam ultricies non sollicitudin nibh. Vestibulum ut ligula dolor, in placerat tortor. Sed nec lacus sed nibh iaculis luctus. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Curabitur rutrum, diam vel tempor commodo, augue nunc viverra risus, in pellentesque neque justo eget dolor. Maecenas quis odio leo, a auctor lorem.
Curabitur dapibus odio quis enim hendrerit eu placerat lorem accumsan. Phasellus sagittis, orci vel laoreet molestie, urna orci imperdiet elit, quis ultricies orci mauris vel ante. Cras pharetra, nisl a sagittis feugiat, turpis magna placerat sem, sed euismod erat elit in magna. Phasellus blandit ullamcorper diam vel porta. Vivamus mollis, metus nec tincidunt venenatis, risus odio sodales risus, vitae ultrices est nisi eget ante. Aenean eget nisi mi. Nulla non nulla nec metus rhoncus congue. Curabitur quis nunc nibh. Cras metus lorem, euismod ornare mattis sagittis, ultrices eget turpis. Integer quis dui tellus. Morbi vel dolor sit amet metus eleifend fringilla. Fusce nunc neque, ultricies et commodo semper, dignissim vitae tortor. Phasellus et ipsum quis sapien accumsan auctor. Morbi congue nulla vel tortor aliquet imperdiet. Morbi eget odio elit, et cursus odio. Quisque a velit diam. Duis urna libero, tempus non mattis a, convallis ac erat. Etiam vel dui posuere lectus auctor viverra vitae id eros. Maecenas mollis eros non elit sollicitudin quis fermentum diam lacinia. Quisque at ante nibh, a molestie ligula.
Sed et enim nunc, nec vehicula sem. Sed risus orci, auctor et dictum at, hendrerit eu augue. Curabitur sed ante non quam fermentum vehicula. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam tincidunt dictum molestie. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Phasellus nec urna ut lorem tempus aliquet eget nec lectus. Phasellus quis venenatis tortor. Integer elementum, sapien at feugiat cursus, tortor sapien adipiscing massa, non molestie elit lacus vel velit. Suspendisse sit amet sem id libero auctor pharetra sit amet ut dui. Aenean sit amet tellus sit amet ante congue faucibus. Nullam hendrerit, justo et iaculis tristique, ligula risus pretium erat, sed tempus lacus felis ut nulla.
EOT;

  /**
   * Generate a random string containing letters.
   *
   * The string will always start with a letter. The letters may be upper or
   * lower case. This method is better for restricted inputs that do not
   * accept certain characters. For example, when testing input fields that
   * require machine readable values (i.e. without spaces and non-standard
   * characters) this method is best.
   *
   * Do not use this method when testing unvalidated user input. Instead, use
   * DrupalWebTestCase::randomString().
   *
   * @param int $length
   *   Length of random string to generate.
   *
   * @return string
   *   Randomly generated string.
   *
   * @see DrupalWebTestCase::randomString()
   */
  public static function name($length = 8) {
    $values = array_merge(range(65, 90), range(97, 122));
    $max = count($values) - 1;
    $str = chr(mt_rand(97, 122));
    for ($i = 1; $i < $length; $i++) {
      $str .= chr($values[mt_rand(0, $max)]);
    }

    return $str;
  }

  /**
   * Generates a random string of ASCII characters of codes 32 to 126.
   *
   * The generated string includes alpha-numeric characters and common
   * miscellaneous characters. Use this method when testing general input
   * where the content is not restricted.
   *
   * Do not use this method when special characters are not possible (e.g., in
   * machine or file names that have already been validated); instead,
   * use DrupalWebTestCase::randomName().
   *
   * @param int $length
   *   Length of random string to generate.
   *
   * @return string
   *   Randomly generated string.
   *
   * @see DrupalWebTestCase::randomName()
   */
  public static function string($length = 8) {
    $str = '';
    for ($i = 0; $i < $length; $i++) {
      $str .= chr(mt_rand(32, 126));
    }

    return $str;
  }

  /**
   * Helper to generate LoremIpsum content.
   *
   * @param int $count
   *   Number of pieces to generate. Defaults to 10.
   * @param string $type
   *   Piece type: 'words' or 'paragraphs'. Defaults to 'words'.
   * @param bool $html
   *   Flag to generate content with HTML. Defaults to FALSE.
   * @param bool $headers
   *   Flag to add headers to content. Defaults to FALSE.
   * @param bool $start_lorem
   *   Flag to start with 'Lorem Ipsum..' words. Defaults to FALSE.
   *
   * @return string
   *   Generated text.
   */
  public static function lipsum($count = 10, $type = 'words', $html = FALSE, $headers = FALSE, $start_lorem = FALSE) {
    $text = self::$loremText;

    $inline_tags = ['i', 'b', 'span'];
    $block_tags = ['p', 'div'];
    $heading_tags = ['h1', 'h2', 'h3'];

    $paragraphs = preg_split('/\R/', $text);
    $words = explode(' ', $text);
    array_walk($words, function (&$word) {
      $word = preg_replace('/[[:punct:]]/', '', $word);
      $word = preg_replace('/\s*/', '', $word);
      $word = strtolower($word);
    });

    if ($type == 'words') {
      if ($start_lorem) {
        $words = array_slice($words, 0, $count);
      }
      else {
        $words = self::arrayItems($words, $count);
      }

      // Wrap words in random html tags.
      if ($html) {
        $html_words = self::arrayItems($words, rand(1, count($words)));
        array_walk($words, function (&$word) use ($html_words, $inline_tags) {
          $tags = self::arrayItems($inline_tags, 1);
          $tag = reset($tags);
          if (in_array($word, $html_words)) {
            $word = "<$tag>$word</$tag>";
          }
        });
      }

      $output = implode(' ', $words);
    }
    else {
      if ($start_lorem) {
        $paragraphs = array_slice($paragraphs, 0, $count);
      }
      else {
        $paragraphs = self::arrayItems($paragraphs, $count);
      }

      if ($html) {
        array_walk($paragraphs, function (&$paragraph) use ($block_tags) {
          $tags = self::arrayItems($block_tags, 1);
          $tag = reset($tags);
          $paragraph = "<$tag>$paragraph</$tag>";
        });
      }

      if ($html && $headers) {
        $lines_with_headings = [];
        // Insert a header before each line.
        foreach ($paragraphs as $paragraph) {
          $tags = self::arrayItems($heading_tags, 1);
          $tag = reset($tags);
          $lines_with_headings[] = "<$tag>" . self::lipsum(rand(2, 10)) . "</$tag>";
          $lines_with_headings[] = $paragraph;
        }
        $paragraphs = $lines_with_headings;
      }

      $output = implode(PHP_EOL, $paragraphs);
    }

    return $output;
  }

  /**
   * Helper to get random ip address.
   */
  public static function ip() {
    return long2ip(rand(0, 4294967295));
  }

  /**
   * Return random phone number according to specified format.
   *
   * @param string $format
   *   Format string as a sequence of numeric and non-numeric characters.
   *   Defaults to '00 0000 0000'. Number will be generated based on number of
   *   numeric characters.
   *
   * @return string
   *   Random phone number formatted according to provided format or FALSE if
   *   provided format is invalid.
   */
  public static function phone($format = '00 0000 0000') {
    $result = '';

    // Count all numeric characters.
    $count = preg_match_all("/[0-9]/", $format, $matches);
    if ($count === FALSE) {
      return '';
    }

    // Generate random number.
    $phone = rand(pow(10, $count), pow(10, $count + 1) - 1);

    $fpos = 0;
    $spos = 0;
    while ($fpos <= (strlen($format) - 1)) {
      $c = substr($format, $fpos, 1);
      if ($c == '\\') {
        $fpos++;
        $c = substr($format, $fpos, 1);
        $result .= $c;
        $spos++;
      }
      elseif (ctype_digit($c) || ctype_alpha($c)) {
        $result .= substr($phone, $spos, 1);
        $spos++;
      }
      else {
        $result .= substr($format, $fpos, 1);
      }
      $fpos++;
    }

    return $result;
  }

  /**
   * Return random email according to test accounts naming schema.
   *
   * Always use this method when creating test accounts.
   *
   * @param string $domain
   *   Optional email domain. Defaults to 'example.com'.
   *
   * @return string
   *   Random email address.
   */
  public static function email($domain = 'example.com') {
    return self::name() . '@' . $domain;
  }

  /**
   * Return random DOB.
   *
   * Always use this method when creating test accounts.
   *
   * @param string $format
   *   Date format to return result. Defaults to year ('Y').
   * @param int $min
   *   Minimum age in years. Defaults to 18.
   * @param int $max
   *   Maximum age in years. Defaults to 80.
   *
   * @return string
   *   Random date of birth.
   */
  public static function dob($format = 'Y', $min = 18, $max = 80) {
    $start = mktime(NULL, NULL, NULL, NULL, NULL, date('Y') - $max);
    $end = mktime(NULL, NULL, NULL, NULL, NULL, date('Y') - $min);

    return date($format, mt_rand($start, $end));
  }

  /**
   * Helper function to generate random path.
   *
   * @param string $path
   *   Optional path containing placeholders (% or %name) to be replaced.
   *
   * @return string
   *   Generated path.
   */
  public static function path($path = NULL) {
    if ($path === NULL) {
      return self::name(16);
    }

    // Handle slashes.
    // Handle %placeholders.
    $replacements = array_map([__CLASS__, 'name'], array_fill(0, 20, 10));
    $path = preg_replace(['/(%[^\/]*)/i'], $replacements, $path);

    return $path;
  }

  /**
   * Helper to get random array items.
   */
  public static function arrayItems($haystack, $count = 1) {
    $haystack_keys = array_keys($haystack);
    shuffle($haystack_keys);
    $haystack_keys = array_slice($haystack_keys, 0, $count);

    return array_intersect_key($haystack, array_flip($haystack_keys));
  }

}
