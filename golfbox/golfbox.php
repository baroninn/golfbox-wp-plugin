<?php
/*
Plugin Name: GolfBox
Description: GolfBox Kalender / Nyheder
Author: Karsten Schmidt @ DLX
Version: 1.1
Author URI: http://dlx.dk/kontakt
*/

// Shortcodes

add_shortcode( 'golfbox_news', 'golfbox_news' );
add_shortcode( 'golfbox_newsteaser', 'golfbox_newsteaser' );
add_shortcode( 'golfbox_calendar', 'golfbox_calendar' );
add_shortcode( 'golfbox_calendar_items', 'golfbox_calendar_items' );

function golfbox_news( $vars ) {
  define( 'DONOTCACHEPAGE', true );

  if (!$vars['length']) $vars['length'] = 1000;
  if (!$vars['readmore']) $vars['readmore'] = 'L&aelig;s mere';

  $client = golfbox_connect_news();

  if ($_GET['newsid']) {
    $params = array(
      'News_GUID' => $_GET['newsid'],
    );
    $soap = $client->GetItem($params);

    if ($soap->GetItemResult->any) {
      $xml = simplexml_load_string($soap->GetItemResult->any);

      $return = '<table class="golfbox_newsitem_table">'.PHP_EOL;
      foreach ($xml->xpath('//NewsItem') as $item) {
        $return .= '<tr><td><span class="golfbox_newsitem_header">'.htmlentities($item->Subject,ENT_QUOTES,"UTF-8").'</span><br />'.PHP_EOL;

        if ($vars['showdate']) $return .= '<span class="golfbox_newsitem_date">'.substr($item->PublishDate,0,2).'/'.substr($item->PublishDate,3,2).'-'.substr($item->PublishDate,6,4).'</span>'.PHP_EOL;

        $return .= '<div>'.PHP_EOL;
	$return .= str_replace(array("&lt;","&gt;"),array("<",">"),$item->Body);
        $return .= '</div></td></tr>'.PHP_EOL;

      }

      $return .= '</table>'.PHP_EOL;

    }

  } else {
    $params = array(
      'HeadersOnly' => false,
    );
    $soap = $client->GetList($params);

    if ($soap->GetListResult->any) {
      $xml = simplexml_load_string($soap->GetListResult->any);

      $return = '<table class="golfbox_news_table">'.PHP_EOL;

      $count=0;
      foreach ($xml->xpath('//NewsItem') as $item) {
        $count++;
        if ($vars['count'] && $count > $vars['count']) break;

        $return .= '<tr><td><span class="golfbox_news_header">'.htmlentities($item->Subject,ENT_QUOTES,"UTF-8").'</span><br />'.PHP_EOL;

        if ($vars['showdate']) $return .= '<span class="golfbox_news_date">'.substr($item->PublishDate,0,2).'/'.substr($item->PublishDate,3,2).'-'.substr($item->PublishDate,6,4).'</span>'.PHP_EOL;

        $return .= '<div>'.PHP_EOL;
        // $return .= htmlentities(substr(golfbox_strip_stuff($item->Body),0,$vars['length']),ENT_QUOTES,"UTF-8");
        $return .= substr(golfbox_strip_stuff($item->Body),0,$vars['length']);
        if (strlen(golfbox_strip_stuff($item->Body))>$vars['length']) $return .= '...';
        $return .= '<br /><a href="?newsid='.$item->GUID.'">'.$vars['readmore'].'</a></div></td></tr>'.PHP_EOL;
      }

      $return .= '</table>'.PHP_EOL;
    }
  }
  return $return;
}

function golfbox_newsteaser( $vars ) {
  define( 'DONOTCACHEPAGE', true );

  if (!$vars['length']) $vars['length'] = 1000;

  $client = golfbox_connect_news();

  $params = array(
    'HeadersOnly' => false,
  );
  $soap = $client->GetList($params);

  if ($soap->GetListResult->any) {
    $xml = simplexml_load_string($soap->GetListResult->any);

    $return = '<table class="golfbox_newsteaser_table">'.PHP_EOL;

    $count=0;
    foreach ($xml->xpath('//NewsItem') as $item) {
      $count++;
      if ($vars['count'] && $count > $vars['count']) break;

      $return .= '<tr><td><span class="golfbox_newsteaser_header">'.htmlentities($item->Subject,ENT_QUOTES,"UTF-8").'</span><br />'.PHP_EOL;

      if ($vars['showdate']) $return .= '<span class="golfbox_newsteaser_date">'.substr($item->PublishDate,0,2).'/'.substr($item->PublishDate,3,2).'-'.substr($item->PublishDate,6,4).'</span>'.PHP_EOL;

      $return .= '<div><a href="'.$vars["url"].'?newsid='.$item->GUID.'">'.PHP_EOL;
      //$return .= htmlentities(substr(golfbox_strip_stuff($item->Body),0,$vars['length']),ENT_QUOTES,"UTF-8");
      $return .= substr(golfbox_strip_stuff($item->Body),0,$vars['length']);
      if (strlen(golfbox_strip_stuff($item->Body))>$vars['length']) $return .= '...';
      $return .= '</a></div></td></tr>'.PHP_EOL;

    }

    $return .= '</table>'.PHP_EOL;
    return $return;
  }
}

function golfbox_calendar( $vars ) {
  define( 'DONOTCACHEPAGE', true );

  if (!$vars['type']) $vars['type'] = 'All';
  if (!$vars['monthnames']) $vars['monthnames'] = "Januar,Februar,Marts,April,Maj,Juni,Juli,August,September,Oktober,November,December";
  if (!$vars['daynames']) $vars['daynames'] = "Ma,Ti,On,To,Fr,L&oslash;,S&oslash;";
  if (!$vars['prevtext']) $vars['prevtext'] = "Forrige";
  if (!$vars['nexttext']) $vars['nexttext'] = "N&aelig;ste";

  wp_register_style('fancybox','//cdn.jsdelivr.net/fancybox/2.1.4/jquery.fancybox.css');
  wp_register_style('fancybox-buttons','//cdn.jsdelivr.net/fancybox/2.1.4/helpers/jquery.fancybox-buttons.css');
  wp_register_style('fancybox-thumbs','//cdn.jsdelivr.net/fancybox/2.1.4/helpers/jquery.fancybox-thumbs.css');
  wp_enqueue_style('fancybox');
  wp_enqueue_style('fancybox-buttons');
  wp_enqueue_style('fancybox-thumbs');

  wp_register_script('fancybox','//cdn.jsdelivr.net/fancybox/2.1.4/jquery.fancybox.pack.js',array('jquery'));
  wp_register_script('fancybox-buttons','//cdn.jsdelivr.net/fancybox/2.1.4/helpers/jquery.fancybox-buttons.js',array('fancybox'));
  wp_register_script('fancybox-media','//cdn.jsdelivr.net/fancybox/2.1.4/helpers/jquery.fancybox-media.js',array('fancybox'));
  wp_register_script('fancybox-thumbs','//cdn.jsdelivr.net/fancybox/2.1.4/helpers/jquery.fancybox-thumbs.js',array('fancybox'));
  wp_enqueue_script('fancybox');
  wp_enqueue_script('fancybox-buttons');
  wp_enqueue_script('fancybox-media');
  wp_enqueue_script('fancybox-thumbs');

  $month = ($_GET[calmonth]) ? $_GET[calmonth] : date("m");
  $year = ($_GET[calyear]) ? $_GET[calyear] : date("Y");

  $today = date("Y-m-d");
  
  $monthnames = explode(",", ",".utf8_encode($vars['monthnames']));
  $daynames = explode(",", ",".utf8_encode($vars['daynames']));
  $monthname = $monthnames[$month*1];

  $days = date ("t", mktime(0,0,0,$month,1,$year));

  $day_of_week = date("w",mktime(0,0,0,$month,1,$year))-1;
  if ($day_of_week<0) $day_of_week = 6;

  $month = $month*1;
  if ($month<10) $month = "0".$month;

  $fromdate = date("Y-m-d", mktime(0,0,0,$month,1,$year));
  $todate = date("Y-m-d", mktime(0,0,0,$month+1,1,$year));

  $prevyear = date("Y", mktime(0,0,0,$month-1,1,$year));
  $prevmonth = date("m", mktime(0,0,0,$month-1,1,$year));
  $nextyear = date("Y", mktime(0,0,0,$month+1,1,$year));
  $nextmonth = date("m", mktime(0,0,0,$month+1,1,$year));

  $_GET[calmonth]=$prevmonth;
  $_GET[calyear]=$prevyear;
  foreach($_GET as $key => $value)
  {
    if ($prevqs) $prevqs .= "&amp;";
    $prevqs .= "$key=$value";
  }
  $_GET[calmonth]=$nextmonth;
  $_GET[calyear]=$nextyear;
  foreach($_GET as $key => $value)
  {
    if ($nextqs) $nextqs .= "&amp;";
    $nextqs .= "$key=$value";
  }

  $url = parse_url($_SERVER['REQUEST_URI']);

  $prev = '<a href="'.$url['path'].'?'.$prevqs.'">'.utf8_encode($vars['prevtext']).'</a>';
  $next = '<a href="'.$url['path'].'?'.$nextqs.'">'.utf8_encode($vars['nexttext']).'</a>';

  $client = golfbox_connect_cal();

  $params = array(
    'StartDate' => $year.'-'.$month.'-01',
    'EndDate' => $year.'-'.$month.'-'.$days,
    'Type' => $vars['type'],
  );
  $soap = $client->GetEvents($params);

  if ($soap->GetEventsResult->any) {
    $xml = simplexml_load_string($soap->GetEventsResult->any);

    foreach ($xml->xpath('//Event') as $item)
    {
      list($daytmp,$null) = explode("-", $item->StartTime);
      $items["$daytmp"]++;

      $popup[$daytmp] .= '<b>'.htmlentities($item->Subject,ENT_QUOTES,"UTF-8").'</b><br />'.PHP_EOL;
      $popup[$daytmp] .= substr($item->StartTime,0,10).' - '.substr($item->StartTime,11,5).'-'.substr($item->EndTime,11,5).'<br />'.PHP_EOL;
      $popup[$daytmp] .= str_replace(array("&lt;","&gt;"),array("<",">"),htmlentities($item->Description,ENT_NOQUOTES,"UTF-8")).'<br /><br />'.PHP_EOL;

    }
    $return  = '<div style="display:none;">';
    foreach ($popup as $day => $text) {
      $day = $day*1;
      $return .= '<div class="golfbox_popup" id="day'.$day.'">'.$text.'</div>'.PHP_EOL;;
    }
    $return .= '</div>';

    $return .= '<table class="golfbox_calendar_table">'.PHP_EOL;
    $return .= '<tr><td colspan="7" class="golfbox_calendar_header">'.$monthname.' '.$year.'</td></tr>';
    $return .= '<tr>';
    for($i=1;$i<=7;$i++)
    {
      $return .= '<td class="golfbox_calendar_day_header">'.$daynames[$i].'</td>';
    }
    $return .= '</tr>'.PHP_EOL;

    $return .= '<tr>';
    $j=1;
    for($i=0;$i<$day_of_week;$i++)
    {
      $return .= '<td class="golfbox_calendar_day_empty"> </td>';
      $j++;
    }
    for($i=1;$i<=$days;$i++)
    {
      if ($j==8)
      {
        $return .= '</tr><tr>';
        $j=1;
      }
      $class = 'golfbox_calendar_day_noevent';
      $day = ($i<10) ? "0".$i : $i;
      if ($today=="$year-$month-$day") $class = 'golfbox_calendar_day_today';
      if ($items[$day])
      {
        $return .= '<td class="golfbox_calendar_day_event"><a href="#day'.$i.'" class="golfbox-popup golfbox_calendar_day_event">'.$i.'</a></td>';
      }
      else
      {
        $return .= '<td class="'.$class.'">'.$i.'</td>';
      }
      $j++;
    }
    for ($i=$j;$i<=7;$i++)
    {
      $return .= '<td class="golfbox_calendar_day_empty"> </td>';
    }

    $return .= '</tr><tr><td></td></tr>';
    $return .= '</tr><tr><td colspan="7" class="golfbox_calendar_footer"> '.$prev.' | '.$next.' </td></tr>'.PHP_EOL;
    $return .= '</table>'.PHP_EOL;

    $return .= '<script type="text/javascript">'.PHP_EOL;
    $return .= 'jQuery(document).ready(function($){'.PHP_EOL;
    $return .= ' $(".golfbox-popup").fancybox();'.PHP_EOL;
    $return .= '})'.PHP_EOL;
    $return .= '</script>'.PHP_EOL;

    return $return;
  }

}

function golfbox_calendar_items( $vars ) {
  define( 'DONOTCACHEPAGE', true );

  if (!$vars['type']) $vars['type'] = 'All';
  if (!$vars['monthnames']) $vars['monthnames'] = "Januar,Februar,Marts,April,Maj,Juni,Juli,August,September,Oktober,November,December";
  if (!$vars['daynames']) $vars['daynames'] = "Mandag,Tirsdag,Onsdag,Torsdag,Fredag,L&oslash;rdag,S&oslash;ndag";

  $montharr = explode(",", utf8_encode($vars['monthnames']));
  $dayarr = explode(",", utf8_encode($vars['daynames']));

  $month = date('n');
  if ($_GET['month']) $month = $_GET['month']*1;
  $year = date('Y');

  $numberofdays =  date ("t", mktime(0,0,0,$month,1,$year));

  $url = parse_url($_SERVER['REQUEST_URI']);

  $return  = $montharr[$month-1].' '.$year.'<br />'.PHP_EOL;
  $return .= '<table class="golfbox_calendar_items_months_table"><tr>';
  $i = 0;
  foreach($montharr as $_month)
  {
    $return .= '<td><b><a href="'.$url['path'].'?month='.($i+1).'">'.$_month.'</a></b></td>';
    $i++;
  }
  $return .= '</tr>';
  $return .= '</table>';


  if ($month < 10) $month = "0".$month;
  $datamonth = date("Y").'-'.$month;
  $startdate = $datamonth."-01";
  $enddate = $datamonth."-".$numberofdays;

  $client = golfbox_connect_cal();

  $params = array(
    'StartDate' => $startdate,
    'EndDate' => $enddate,
    'Type' => $vars['type'],
  );
  $soap = $client->GetEvents($params);

  if ($soap->GetEventsResult->any) {
    $xml = simplexml_load_string($soap->GetEventsResult->any);

    foreach ($xml->xpath('//Event') as $item)
    {
      $dagen = substr($item->StartTime,0,2)*1;
      $header_out[$dagen][] = htmlentities($item->Subject,ENT_QUOTES,"UTF-8");
      $content_out[$dagen][] = htmlentities($item->Description,ENT_QUOTES,"UTF-8");
      $fromdate = explode(" ",htmlentities($item->StartTime,ENT_QUOTES,"UTF-8"));
      $todate = explode(" ",htmlentities($item->EndTime,ENT_QUOTES,"UTF-8"));
      $starttime[$dagen][] = substr($fromdate[1],0,5)."-".substr($todate[1],0,5);
    }

    $return .= '<table class="golfbox_calendar_items_table">';
    $return .= '<tr class="golfbox_calendar_items_tr_header"><td class="golfbox_calendar_items_date_even">Dato</td>';
    $return .= '<td class="golfbox_calendar_items_day_even">Dag</td>';
    $return .= '<td class="golfbox_calendar_items_event_even">Aktivitet</td></tr>'.PHP_EOL;
    for($i=1; $i<=$numberofdays;$i++)
    {
      $daynum = $i;
      if ($i<10) $daynum = "0".$i;

      $day = $datamonth.'-'.$daynum;
      $day = date('w',strtotime($day))-1;
      if ($day == -1) $day = 6;

      $dayname = $dayarr[$day];

      $class = ($i % 2) ? "odd" : "even";

      $return .= '<tr class="golfbox_calendar_items_tr_'.$class.'">';
      $return .= '<td class="golfbox_calendar_items_date_'.$class.'">'.$i.'</td><td class="golfbox_calendar_items_day_'.$class.'">'.$dayname.'</td><td class="golfbox_calendar_items_event_'.$class.'">'.PHP_EOL;

      if(is_array($header_out[$i]))
      {
        $j = 0;
        foreach($header_out[$i] as $headers)
        {
          $return .= '<div class="golfbox_calendar_items_time">'.$starttime[$i][$j].'</div>'.PHP_EOL;
          $return .= '<div class="golfbox_calendar_items_header">'.utf8_encode($headers).'</div'.PHP_EOL;
          $return .= '<div class="golfbox_calendar_items_text">'.utf8_encode($content_out[$i][$j]).'</div>'.PHP_EOL;
          $j++;
        }
      }
      $return .= '</td></tr>'.PHP_EOL;
    }
    $return .= '</table>';
    


    return $return;
  }

  // return '<pre>'.print_r($xml, true).'</pre>';
}

function golfbox_connect_news() {
  $wsdl_url = 'http://golfbox.dk/web/services/webservice/news.asmx?WSDL';
  $client = new SOAPClient($wsdl_url);

  $header = new SoapHeader('http://golfbox.net/web/services/webservice',
                           'UserCredentials',
                           array('Account' => get_option('golfbox_account'), 
                                 'Username' => get_option('golfbox_username'), 
                                 'Password' => get_option('golfbox_password')
                                 )
                           );

  $client->__setSoapHeaders($header);

  return $client;
}

function golfbox_connect_cal() {
  $wsdl_url = 'http://golfbox.dk/web/services/webservice/calendar.asmx?WSDL';
  $client = new SOAPClient($wsdl_url);

  $header = new SoapHeader('http://golfbox.net/web/services/webservice',
                           'UserCredentials',
                           array('Account' => get_option('golfbox_account'), 
                                 'Username' => get_option('golfbox_username'), 
                                 'Password' => get_option('golfbox_password')
                                 )
                           );

  $client->__setSoapHeaders($header);

  return $client;
}


function golfbox_strip_stuff($in) {
  $search = array ('@<script[^>]*?>.*?</script>@si', // Strip out javascript
                   '@<[\/\!]*?[^<>]*?>@si',          // Strip out HTML tags
                   '@([\r\n])[\s]+@',                // Strip out white space
                   '@&(quot|#34);@i',                // Replace HTML entities
                   '@&(amp|#38);@i',
                   '@&(lt|#60);@i',
                   '@&(gt|#62);@i',
                   '@&(nbsp|#160);@i',
                   '@&(iexcl|#161);@i',
                   '@&(cent|#162);@i',
                   '@&(pound|#163);@i',
                   '@&(copy|#169);@i',
                   '@&#(\d+);@e');                    // evaluate as php

  $replace = array ('',
                   '',
                   '\1',
                   '"',
                   '&',
                   '<',
                   '>',
                   ' ',
                   chr(161),
                   chr(162),
                   chr(163),
                   chr(169),
                   'chr(\1)');

  $return = preg_replace($search,$replace,$in);
  $return = str_replace(".", ". ", $return);
  $return = str_replace(".  ", ". ", $return);

  return $return;
}

// Style in header

add_action( 'wp_head', 'golfbox_header' );

function golfbox_header() {
?>
<style type='text/css'>
<?php echo get_option('golfbox_style'); ?>
</style>
<?php
}


// Admin stuff

add_filter( 'plugin_action_links', 'golfbox_plugin_action_links', 10, 2 );
add_action( 'admin_menu', 'golfbox_admin_menu' );

function golfbox_plugin_action_links( $links, $file ) {
  if ( $file == plugin_basename( dirname(__FILE__).'/golfbox.php' ) ) {
    $links[] = '<a href="' . admin_url( 'plugins.php?page=golfbox-config' ) . '">'.__( 'Settings' ).'</a>';
  }

  return $links;
}

function golfbox_admin_menu() {
  add_submenu_page('plugins.php', 'GolfBox '.__('Settings'), 'GolfBox '.__('Settings'), 'manage_options', 'golfbox-config', 'golfbox_conf');
  register_setting( 'golfbox-options', 'golfbox_account' );
  register_setting( 'golfbox-options', 'golfbox_username' );
  register_setting( 'golfbox-options', 'golfbox_password' );
  register_setting( 'golfbox-options', 'golfbox_style' );

}

function golfbox_conf() {
  if (current_user_can('manage_options')) {
    ?>
    <div class="wrap">
    <?php screen_icon(); ?>
    <h2>GolfBox <?=__('Settings')?></h2>
    <form method="post" action="options.php">
    <?php settings_fields( 'golfbox-options' ); ?>
    <?php do_settings_sections( 'golfbox' ); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">Account</th>
        <td><input type="text" name="golfbox_account" value="<?php echo get_option('golfbox_account'); ?>" /></td>
        </tr>

        <tr valign="top">
        <th scope="row">Username</th>
        <td><input type="text" name="golfbox_username" value="<?php echo get_option('golfbox_username'); ?>" /></td>
        </tr>

        <tr valign="top">
        <th scope="row">Password</th>
        <td><input type="text" name="golfbox_password" value="<?php echo get_option('golfbox_password'); ?>" /></td>
        </tr>

        <tr valign="top">
        <th scope="row">CSS</th>
        <td><textarea rows="10" cols="100" name="golfbox_style"><?php echo get_option('golfbox_style'); ?></textarea></td>
        </tr>
    </table>      
    <?php submit_button(); ?>
    </form>
    </div>
    <?php
  }
}
