<?php
// Importing macros, reformatting them a bit, and exporting as a CSV for easy editing/reviwing by account managers.

$file = "macros.json";


$json_data = json_decode(file_get_contents($file), true);


echo " \n ################################################### \n";

echo "Imported JSON data\n";

echo "Opening macro.csv for writing \n";

$file = "macros.csv";

if(!$fp = fopen($file, 'a')) {
	echo "Could not open file($file)";
}

// Write out the header first
fwrite($fp, '"Number","title","Macro Verbiage"');
//add a line break
fwrite($fp, "\n");

echo "Wrote header \n";

$i=1;
$x = 1;

echo "grabbing title and macro verbiage \n";

foreach ($json_data{'macros'} as $json) {
	
	//Grab the title here echo "title : \n";
	//print $i . " title: " . $json{'title'} . "\n";
	$title = $json{'title'};

	//do a foreach on $json{'actions'} as it's an array

	foreach ($json{'actions'} as $field_arr) {

		//Some have an array instead of one element
		if(is_array($field_arr{'value'})) {

			$x+=1; //for troubleshooting
			//print $field_arr{'value'}{'1'};

			$comment = $field_arr{'value'}{'1'};
			
		}
		else {
			//These are not an array, but the comment is sometimes in element 1, or 2, or  3,
			// so we only need to grab the element value with the most characters
			if(strlen($field_arr{'value'}) > 25) {
		
				//print "field value" .$i .": " . $field_arr{'value'} ."\n";

				$comment = $field_arr{'value'};
			}
		}
	}
	$i++;

	//Now write out to a CSV file
	fwrite($fp, "'$i','$title','$comment'");
	fwrite($fp, "\n");

}
fclose($fp);

//print "there were ". $x . " array macros\n";
print "Done! writing to a file.";


?> 
