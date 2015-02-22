#!/usr/bin/perl 
#this is the perl script for the IP Tool that logs into each state to get and export 
# a list of the modems that are advertising at the time this script is run.
# It uses Net::Telnet::Cisco to log into routers and show ip access-lists 50 and show ip route | inc
# by Riley Withers

use Net::Telnet::Cisco;
use DBI;
my $filedir="/tmp";

#these variables are for connecting to the db
$driver         = "mysql";
$db_user        = "user";
$db_password    = 'password';
$database       = "statics";
$db_hostname    = "localhost";

$dsn = "DBI:$driver:database=$database:host=$db_hostname";
$dbh = DBI->connect($dsn, $db_user, $db_password);

#empty out the database table first 
$ste = $dbh-> prepare ("DELETE FROM ipreclamation");
$ste->execute();

# A hash with a key value pair for each state and router ip
my %routerlist = ("nv" =>'xxx.xx.xx.66',
				"wa" =>'xxx.xx.xx.64',
				"ore" =>'xxx.xx.xx.5',
				"ca" =>'xxx.xx.xx.75');
		
# Pass each key/value pair to a subroutine that will parse 
#  and write out to a separate text file for each state for historical purpose
while ( ($key, $value) = each %routerlist)
{
	&get_net_id($key => $value)
}


###################################
# subroutine to parse each ripblock modem and the state it's in
sub get_net_id
{
	#print "\n now getting modems for $_[0] \n \n";
	#empty out the following arrays before we get the next state's rip blocks and rip modems
	#splice(@block); 
	#splice(@ripblock); 
	splice(@ip); 
	splice(@ripblocklist); 
	splice(@modemlist);
	splice(@netline);
	splice(@netandsub);
	splice(@statenetandsub);
	
	# for better readability, re-assign the default variables
	my $state = $_[0]; #The name of the state
	my $router = $_[1]; #The ip address of the router 

	# Query the database and get the rip blocks ONLY for the state value in $state
	 $sth = $dbh -> prepare ("SELECT ripblock FROM ripblocks WHERE state = '$state'");
	 $sth->execute();
	 $i=1;
	while(@data = $sth->fetchrow_array)
	{
		print "ripblock # $i: $data[0] \n";
		push(@ripblocklist, $data[0]); #add all the ripblocks into the ripblocklist array
		$i++;
	}
	
	# Telnet into one router for each state to get the modems that are advertising in that state
	$session = Net::Telnet::Cisco->new(Errmode => 'return', Timeout => 600);
	$session->open($router);
	$session->login('username', 'password');
	$session->cmd('terminal length 0');
	
	# Loop through each ripblock in that particular state
	foreach $ip(@ripblocklist)
	{
		
		#@ip = split (/,/, $ip);
		$modemlistcmd = "show ip route | inc " . $ip;
		@modemlist = $session->cmd(String=>$modemlistcmd,Timeout=>600);
						
			#Now we are going to only get the  network id and subnet and put in a database table to compare with
			# all records kept and IP Allocation Tool  of allocated IP's to reclaim unsused static IP's.
			#print "modem list: @modemlist \n";
			foreach(@modemlist)
			{
				#print "\n ripblock: $_ \n";
				#break appart each line by the white space
				@netline = split (/\s+/, $_);
				
				#loop to select the correct array element we need and build a new array
				if($netline[0] eq 'R')
				{
					#print "netlines IP: $netline[1] \n";
					push(@netandsub, $netline[1]);
				}
				else
				{
				#print "netlines IP: $netline[2] \n";
				push(@netandsub, $netline[2]);
				}
			}
			#The network id looks like this: xxx.xxx.xxx/xx.  now split it at the "/" to get net id and subnet
			foreach(@netandsub) 
			{
				@statenetandsub =split(/\//, $_);
				# Now there's 2 elements, the network id and the subnet, e.g. 30 
				#print "$_ \n";
				# now add the state the modem is in and the subnet that was allocated, and export to database.
				#print "$state $statenetandsub[0] $statenetandsub[1] \n";
				$networkid = $statenetandsub[0]; #for readability
				$subnet = $statenetandsub[1]; #for readability
				#print " = $networkid and subnet=$subnet \n";
			
			insert_modems_db_table($networkid,$subnet,$state);
			
			}
		splice(@netandsub);
		splice(@statenetandsub);
	}
	
			
}
sub insert_modems_db_table
{
$networkid = $_[0]; #for readability
$subnet = $_[1]; #for readability
$state =  $_[2]; #for readability

#insert the modems from the function above.
#print "\n now inserting ips into db \n \n";
$sth = $dbh-> prepare ("INSERT INTO ipreclamation SET networkid='$networkid', subnet='$subnet', state='$state'");
 $sth->execute();
}#end of insert_modems_db_table
