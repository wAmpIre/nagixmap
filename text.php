<?php 
	$conf['dbhost']   = 'localhost';
	$conf['dbdb']     = 'nagios';
	$conf['dbprefix'] = 'nagios_';
	$conf['dbuser']   = 'nagios';
	$conf['dbpass']   = 'uzr54kj3';

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
	$width=600;
	$height=400;

	echo "<pre>";
	$myconn = mysql_pconnect( $conf['dbhost'], $conf['dbuser'], $conf['dbpass'] ) or die( mysql_error() );
	mysql_select_db( $conf['dbdb'] ) or die( mysql_error() );

	# FIXME
	$hostname = mysql_escape_string( $_GET['host'] );

	$myresult = mysql_query( $q_host . "'". $hostname . "'" ) or die( mysql_error() );
	$i_host = mysql_fetch_array( $myresult );
	mysql_free_result( $myresult );

	print_r( $i_host );
	echo "Coordiantes for Host: " . coord($width/2, 40) . " and " . coord($height/2, 40);





	echo "\n\n\n\nParents:\n\n";

	$myres_p = mysql_query( $q_parents . $i_host['host_id'] ) or die( mysql_error() );
	$nrofp = mysql_num_rows( $myres_p );
	$x = 20;
	while( $myrow = mysql_fetch_array( $myres_p ) ) {
		print_r( $myrow );
		echo "Coordinates: "  . $x . " and " . ($height/4) . "\n";
		$x += 60;
	}





	echo "\n\n\n\nChildren:\n\n";

	$myres_c = mysql_query( $q_children . $i_host['object_id'] ) or die( mysql_error() );
	$nrofc = mysql_num_rows( $myres_c );
	$x = 20;
	while( $myrow = mysql_fetch_array( $myres_c ) ) {
		print_r( $myrow );
		echo "Coordinates: "  . $x . " and " . ($height*3/4) . "\n";
		$x += 60;
	}





	echo "</pre>";

	mysql_free_result( $myres_p );
	mysql_free_result( $myres_c );

?>
