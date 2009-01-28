<?
	header('Content-Type: image/svg+xml');
	echo "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" standalone=\"no\"?>\n";
?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink">

<?php 
	error_reporting(E_ALL ^E_NOTICE);
	$config = "./config";
	if (is_readable($config . ".php")) {
		include ($config . ".php");
	} else {
		die("<b>$config.php</b> not found");
	}

	if (is_readable($config . "_local.php")) {
		include ($config . "_local.php");
	}

	# Host & Object
	# SELECT host_id,host_object_id,address,name1,name2 FROM nagios_hosts JOIN nagios_objects ON nagios_objects.object_id=nagios_hosts.host_object_id LIMIT 20;
	#
	# Alle parent-Beziehungen:
	# SELECT nagios_host_parenthosts.host_id,parent_host_object_id,nagios_objects.name1 AS host_name,nagios_hosts.address,parentobjects.name1 AS parenthost_name FROM nagios_host_parenthosts JOIN nagios_hosts ON nagios_host_parenthosts.host_id=nagios_hosts.host_id JOIN nagios_objects ON nagios_objects.object_id=nagios_hosts.host_object_id JOIN nagios_objects AS parentobjects ON parentobjects.object_id=nagios_host_parenthosts.parent_host_object_id;
	#
	#
	#
	#
	#
	# 1. object_id holen:
	# SELECT object_id FROM nagios_objects WHERE objecttype_id = 1 AND name1 = 'nova';
	# nova >> 261
	# ipx-corerouter >> 153
	# monitor >> 124
	#
	# 2. host_id & Co. holen:
	# SELECT host_id,display_name,address,notes,notes_url,action_url,statusmap_image,icon_image FROM nagios_hosts WHERE host_object_id=261;
	#
	# Schritt 1+2:
	# SELECT object_id,host_id,display_name,address,notes,notes_url,action_url,statusmap_image,icon_image FROM nagios_objects JOIN nagios_hosts ON nagios_hosts.host_object_id=nagios_objects.object_id  WHERE objecttype_id = 1 AND name1 = 'nova';
	$q_host = 'SELECT object_id,host_id,display_name,address,notes,notes_url,action_url,statusmap_image,icon_image FROM nagios_objects JOIN nagios_hosts ON nagios_hosts.host_object_id=nagios_objects.object_id  WHERE objecttype_id = 1 AND name1 = ';
	# nova/261 >> 400
	# ipx-corerouter/153 >> 366
	# monitor/124 >> 373
	#
	# 3. Parents holen:
	# SELECT parent_host_object_id FROM nagios_host_parenthosts WHERE host_id=400;
	# SELECT parent_host_object_id,name1 FROM nagios_host_parenthosts JOIN nagios_objects ON nagios_host_parenthosts.parent_host_object_id=nagios_objects.object_id WHERE nagios_host_parenthosts.host_id=400;
	# SELECT parent_host_object_id,name1,display_name,address,notes,notes_url,action_url,statusmap_image,icon_image FROM nagios_host_parenthosts JOIN nagios_objects ON nagios_host_parenthosts.parent_host_object_id=nagios_objects.object_id JOIN nagios_hosts ON nagios_objects.object_id=nagios_hosts.host_object_id  WHERE nagios_host_parenthosts.host_id=373;
	$q_parents = 'SELECT parent_host_object_id,name1,display_name,address,notes,notes_url,action_url,statusmap_image,icon_image FROM nagios_host_parenthosts JOIN nagios_objects ON nagios_host_parenthosts.parent_host_object_id=nagios_objects.object_id JOIN nagios_hosts ON nagios_objects.object_id=nagios_hosts.host_object_id  WHERE nagios_host_parenthosts.host_id=';
	# nova/261/400 >> 128//blackhole
	# ipx-corerouter/153/366 >> ---
	# monitor/124/373 >> 99/101/103//esx-32/esx-31/esx-33
	#
	# 4. Childs holen:
	# SELECT nagios_host_parenthosts.host_id,display_name,address,notes,notes_url,action_url,statusmap_image,icon_image FROM nagios_host_parenthosts JOIN nagios_hosts ON nagios_hosts.host_id=nagios_host_parenthosts.host_id WHERE parent_host_object_id=261;
	$q_children = 'SELECT nagios_host_parenthosts.host_id,display_name,address,notes,notes_url,action_url,statusmap_image,icon_image FROM nagios_host_parenthosts JOIN nagios_hosts ON nagios_hosts.host_id=nagios_host_parenthosts.host_id WHERE parent_host_object_id=';
	# nova/261 >> 376/406//nix__switch_swp/quasar
	# ipx-corerouter/153 >> 402/403/411//phoenix/planb/swork
	# monitor/124 >> ---

	function coord( $x, $size ) {
		return( $x-$size/2 );

	}
	function rect( $x, $y, $size, $color="#7F7F7F" ) {
		$r = $size / 10;
		echo '  <rect x="'.$x.'" y="'.$y.'" width="'.$size.'" height="'.$size.'" rx="'.$r.'" ry="'.$r.'" fill="'.$color.'" />' . "\n";
	}
	function text( $x, $y, $text, $color="#000000" ) {
		echo '    <text x="'.$x.'" y="'.($y+4).'" fill="'.$color.'" font-size="9px" text-anchor="middle">' . $text . '</text>' . "\n";
	}


	$hosts_per_line = (int)floor( $conf['width'] / $conf['delta'] - 1 ) ;

	echo '<rect x="0" y="0" width="'.$conf['width'].'" height="'.$conf['height'].'" rx="10" ry="10" fill="#FFFFFF" stroke="#cccccc" />' . "\n" ;


	$myconn = mysql_pconnect( $conf['dbhost'], $conf['dbuser'], $conf['dbpass'] ) or die( mysql_error() );
	mysql_select_db( $conf['dbdb'] ) or die( mysql_error() );

	# FIXME
	$hostname = mysql_escape_string( $_GET['host'] );

	/**********************/
	/* Datenbank-Zugriffe */
	/**********************/

	// Host
	$myresult = mysql_query( $q_host . "'". $hostname . "'" ) or die( mysql_error() );
	$i_host = mysql_fetch_array( $myresult );
	mysql_free_result( $myresult );

	// Parents
	$myres_p = mysql_query( $q_parents . $i_host['host_id'] ) or die( mysql_error() );
	$nrofp = mysql_num_rows( $myres_p );

	// Children
	$myres_c = mysql_query( $q_children . $i_host['object_id'] ) or die( mysql_error() );
	$nrofc = mysql_num_rows( $myres_c );

	/***************************/
	/* Ab hier wird gezeichnet */
	/***************************/

	// Wieviele "Zeilen" brauchen wir? 2 fuer den Host
	$lines  = 2;

	$lines_p = ceil($nrofp / $hosts_per_line);
	if( $lines_p == 0 ) $lines_p = 1;
	$lines_c = ceil($nrofc / $hosts_per_line);
	if( $lines_c == 0 ) $lines_c = 1;

	$lines += $lines_c + $lines_p;

	$lineheight = floor( $conf['height'] / $lines );
	for( $i=1; $i<$lines; $i++ ) {
		echo '<line x1="0" y1="'.($lineheight*$i).'" x2="'.$conf['width'].'" y2="'.($lineheight*$i).'" stroke="#AAAAAA" />' . "\n";
	}
	if( $lineheight >= 85 ) {
		$hostsize = 50;
	} else {
		$hostsize = $lineheight - 35;
	}

	// $fontsize = ceil( $lineheight / 3 );
	$fontsize = (2 * $lineheight - 100) / 4;
	echo '<text x="'.($conf['width']/2).'" y="'.($lineheight*$lines_p+$fontsize).'" fill="#AAAAAA" font-size="'.$fontsize.'px" opacity="0.5" text-anchor="middle">Parents</text>' . "\n";
	echo '<text x="'.($conf['width']/2).'" y="'.($lineheight*($lines_p+2)-5).'" fill="#AAAAAA" font-size="'.$fontsize.'px" opacity="0.5" text-anchor="middle">Children</text>' . "\n";
	// echo '<text x="10" y="30" fill="#AAAAAA" font-size="20px" opacity="0.5">Parents</text>' . "\n";
	// echo '<text x="10" y="'.($lineheight*($lines_p+2)+30).'" fill="#AAAAAA" font-size="20px" opacity="0.5">Children</text>' . "\n";
	// echo '<g transform="rotate(-90,200,200)">' . "\n";
	// echo '<text x="200" y="200" fill="#AAAAAA" font-size="'.$fontsize.'px" opacity="0.5" text-anchor="middle">Parents</text>' . "\n";
	// echo '</g>' . "\n";


	// Parents
	$y = ceil( $lineheight / 2 );
	$x = $conf['width']/2 - ( ($nrofp-1)*$conf['delta'] / 2 );
	$counter = 0;
	while( $myrow = mysql_fetch_array( $myres_p ) ) {
		echo '  <a xlink:href="?host='.$myrow['display_name'].'">' . "\n";
		if( ($counter % $hosts_per_line % 2) == 0 ) {
			$dy = ceil($hostsize/2)+8;
		} else {
			$dy = -ceil($hostsize/2)-8;
		}
		rect( coord($x, $hostsize), coord($y, $hostsize), $hostsize );
		text( $x, ($y+$dy), $myrow['display_name'] );
		echo '  </a>' . "\n";
		$x += $conf['delta'];
		$counter++;
		if( ($counter % $hosts_per_line) == 0 ) {
			$x = $conf['width']/2 - ( ($nrofc-1)*$conf['delta'] / 2 );
			$y += $lineheight;
		}
	}

	// Host
	// $y = ceil( $lineheight * floor( $nrofp / $hosts_per_line + 2 ) );
	$y += $lineheight * 1.5;
	rect( coord($conf['width']/2, 100), coord($y, 100), 100, "#FF0000" );
	text( $conf['width']/2, $y, $i_host['display_name'], "#FFFF00" ); 

	// Children
	if( $nrofc > $hosts_per_line ) {
		$nrofc = $hosts_per_line;
	}
	// $y = ceil( $lineheight * floor( $nrofc / $hosts_per_line + 3) );
	$y += $lineheight * 1.5 ;
	$x = $conf['width']/2 - ( ($nrofc-1)*$conf['delta'] / 2 );
	$counter = 0;
	while( $myrow = mysql_fetch_array( $myres_c ) ) {
		echo '  <a xlink:href="?host='.$myrow['display_name'].'">' . "\n";
		if( ($counter % $hosts_per_line % 2) == 0 ) {
			$dy = ceil($hostsize/2)+8;
		} else {
			$dy = -ceil($hostsize/2)-8;
		}
		rect( coord($x, $hostsize), coord($y, $hostsize), $hostsize, "#3333FF" );
		text( $x, $y+$dy, $myrow['display_name'], "#000000" );
		echo '  </a>' . "\n";
		$x += $conf['delta'];
		$counter++;
		if( ($counter % $hosts_per_line) == 0 ) {
			$x = $conf['width']/2 - ( ($nrofc-1)*$conf['delta'] / 2 );
			$y += $lineheight;
		}
	}




	mysql_free_result( $myres_p );
	mysql_free_result( $myres_c );

?>

</svg>
