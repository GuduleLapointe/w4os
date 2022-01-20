#!/usr/bin/perl

	use strict;
	use warnings;

	use File::Basename;
	use lib dirname (__FILE__) . "/lib";
	require "setupMYSQL.plx";

	use CGI;                             # load CGI routines
	my $q = new CGI;                        # create new CGI object
	print $q->header;

	my $db = GetDB();


	require LWP::UserAgent;

	my $ua = LWP::UserAgent->new;
	$ua->timeout(10);
	$ua->env_proxy;

	## replacing defunct HYPEvents with fork 2DO.pm
	#my $response = $ua->get('http://yourworlds.eu/hypevents/events.json');
	my $response = $ua->get('http://2do.pm/events/events.json');

	my $x = $response->content;
	my %Cat;
	if ($response->is_success) {

	#open (OUT, ">", "../SecondLife/events.json");
	open (OUT, ">", "events.json");
	my $x = $response->decoded_content;
	$x =~ s/}/}\n/g;
	print OUT $x;
	close OUT;

	my $q = 'delete from events';
	$db->Sql($q);
	if ($db->Error) {
		die $db->Error;
	}

	use JSON;

	my @a =  decode_json $response->content;

	use utf8;

	for my $b( @a ) {
	for my $data ( @$b ) {

		my $name = $data->{title};

		my $time = $data->{start};  #2018-08-30T07:00:00+00:00
		my $slurl = $data->{hgurl};

		use HTML::Entities;
		$slurl = encode_entities($slurl);

		my $description =  $data->{description};
		$description = decode_entities($description);
		# remove any possible non-breaking spaces that will make the script fail
		$description =~ s/\xa0/ /g;
		$description =~ s/\xA0/ /g;
		$description =~ s/&amp;nbsp;/ /g;
		$description =~ s/&nbsp;/ /g;

		use HTML::Strip;

		my $hs = HTML::Strip->new();

		$description = $hs->parse( $description );
		$hs->eof;

		# had to comment these out as another source of problem characters
		#utf8::decode($description);

		$name = decode_entities($name);
		# remove any possible non-breaking spaces that will make the script fail
		$name =~ s/\xa0/ /g;
		$name =~ s/\xA0/ /g;
		$name =~ s/&amp;nbsp;/ /g;
		$name =~ s/&nbsp;/ /g;
		# had to comment these out as another source of problem characters
		#utf8::decode($name);

		print "$description\n";
		my @categories = $data->{categories};

		foreach my $category (@categories)
		{
			foreach  my $c (@$category)
			{
				$Cat{$c} ++;
			}
		}

		my $end = $data->{end};

		use Date::Manip;
		my $date1=&ParseDate($time);
		print &UnixDate($date1,"The time is now %T on %b %e, %Y.\n");
		&UnixDate($date1,"The time is now %T on %b %e, %Y.\n");
		my $startdate = &UnixDate($date1,"%Y-%m-%d %H:%M:%S");

		my $h = &UnixDate($date1,"%H");
		my $mn =&UnixDate($date1,"%M");
		my $s =&UnixDate($date1,"%S");

		my $y =&UnixDate($date1,"%Y");
		my $m =&UnixDate($date1,"%m");
		my $dd =&UnixDate($date1,"%d");

		my $secs= &Date_SecsSince1970GMT($m,$dd,$y,$h,$mn,$s) - 60*60 *2;

		my $date2=&ParseDate($end);
		print &UnixDate($date2,"The event ends at %T on %b %e, %Y.\n");
		my $err;
		my $delta=&DateCalc($date1,$date2,\$err);
		print "The duration is $delta\n";
		#0:0:0:0:24:0:0
		my ($a,$b,$c,$d,$day,$min,$sec) = split(':',$delta);

		my $duration = $day * 60 + $min;
		print "The duration is $duration minutes\n";
		# hop://goto.theencoreescape.com:8002/All Saints Ballroom/94/124/34

		my $gateway = '';
		my $simname = '';
		my $port = '';
		my $landingpoint = '';

		print "$data->{hgurl}\n";

		use URI::Escape;

		# clean up front end
		$slurl =~ s/http:\/\///;
		$slurl =~ s/:\/\///;
		$slurl =~ s/\/\///;
		$slurl =~ s/(\d{4}):/$1\//;

		if ($slurl =~ /KARAOKE/i) {
			my $bp = 1;
		}
		if ($slurl =~ /(.*):(\d+)\/(.*?)\/(.*)/ ) {
			$gateway = $1 || '';
			$port = $2 || '';
			$simname = $3 || '';
			$simname = uri_escape_utf8($simname);
			$landingpoint = "//$4" if $4;
		} elsif ($slurl =~ /(.*?):(\d+)\/(.*)/ ){
			#kalasiddhigrid.com:8002:
			$gateway = $1 || '';
			$port = $2 || '';
			$simname = $3 || '';
			$simname = uri_escape_utf8($simname);
			$landingpoint = '';
		} elsif ($slurl =~ /(.*?):(\d+)\// ){
			#kalasiddhigrid.com:8002:
			$gateway = $1 || '';
			$port = $2 || '';
		} elsif ($slurl =~ /(.*?):(\d+)/ ){
			#kalasiddhigrid.com:8002:
			$gateway = $1 || '';
			$port = $2 || '';
		}
		else {
			my $bp = 1;	 # breakpoint
			next;
		}
		$gateway = lc $gateway;
		$gateway =~ s/ //g;

		#secondlife://apps/teleport/goto.theencoreescape.com:8002/All Saints Ballroom/94/124/34
		#secondlife:///app/teleport/3d.gimisa.ca|9000|gimisa3//128/128/25
		$slurl = qq!secondlife://app/teleport/${gateway}|${port}|${simname}${landingpoint}!;

		$description .= "\n\n" . $data->{hgurl};

		print "$slurl\n\n";

		$name = substr($name,0,255);

		my $q = 'insert into events  (simname, category,creatoruuid, owneruuid,name, description,
									  dateUTC,duration,covercharge, coveramount,parcelUUID, globalPos,
									  eventflags) values (?,?,?,?,?,?,?,?,?,?,?,?,?) ';
		if($db->Prepare($q))  {die $db->Error;}
		$startdate = 0;

		my $uuid = '00000000-0000-0000-0000-000000000001';
		if ($db->Execute("$slurl" ,	#simname,
						  0,	 #category
						  $uuid, #creatorUUID,
						  $uuid,	#ownerUUID
						  $name, 	# text,
						  $description,
						  $secs,		# DateUTC format
						  $duration,		#duration
						  0, 		#covercharge
						  0,		#coveramount
						  $uuid,	#parcelUUID
						  '128,128,25',#globalpos
						  0			#eventflags
						  )) {die $db->Error;}
		}
	}

	foreach my $x (keys %Cat) {
		print "$x\n";
	}


}  else {
	print "No events available today";
}



=pod
12:00AM

insert into events  (simname,category,creatoruuid, owneruuid,name, description, dateUTC,duration,covercharge, coveramount,parcelUUID, globalPos,eventflags) values
(	'simname',
	0,
	'00000000-0000-0000-0000-000000000000',
	'00000000-0000-0000-0000-000000000000',
	'test','description',0,
	1000,0,0,'00000000-0000-0000-0000-000000000000',
	'<0,0,0>',
	0

);

+-------------+------------------+------+-----+---------+----------------+
| Field       | Type             | Null | Key | Default | Extra          |
+-------------+------------------+------+-----+---------+----------------+
| owneruuid   | char(36)         | NO   |     | NULL    |                |
| name        | varchar(255)     | NO   |     | NULL    |                |
| eventid     | int(11) unsigned | NO   | PRI | NULL    | auto_increment |
| creatoruuid | char(36)         | NO   |     | NULL    |                |
| category    | int(2)           | NO   |     | NULL    |                |
| description | text             | NO   |     | NULL    |                |
| dateUTC     | int(10)          | NO   |     | NULL    |                |
| duration    | int(10)          | NO   |     | NULL    |                |
| covercharge | tinyint(1)       | NO   |     | NULL    |                |
| coveramount | int(10)          | NO   |     | NULL    |                |
| simname     | varchar(255)     | NO   |     | NULL    |                |
| parcelUUID  | char(36)         | NO   |     | NULL    |                |
| globalPos   | varchar(255)     | NO   |     | NULL    |                |
| eventflags  | int(1)           | NO   |     | NULL    |                |
+-------------+------------------+------+-----+---------+----------------+


literature
music
education
social
The category for an event is stored as a number. The numbers for the
categories are as follows:
0 - Any  (NOTE: Event information will show "*Unspecified*")
18- Discussion
19- Sports
20- Live Music
22- Commercial
23- Nightlife/Entertainment
24- Games/Contests
25- Pageants
26- Education
27- Arts and Culture
28- Charity/Support Groups
29- Miscellaneous

The dateUTC field is a timestamp for the event in UTC time.

The covercharge field is a boolean. Set it to 0 if there is no cover charge
for the event. When covercharge is not 0, the amount is in the coveramount
field. (It seems silly to require the boolean but this has been left in to
avoid any compatability issues.)

The globalPos field is the location of the event as a global grid coordinate.
The format is "x/y/z". where x and y are the grid X and Y positions (times
256) plus the x and y offset within the region named by the simname field.

The eventflags field is 0 for a PG event, 1 for Mature, and 2 for Adult.

=cut
