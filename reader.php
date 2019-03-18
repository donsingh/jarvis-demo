<?php
require 'vendor/autoload.php';

use Google\Cloud\Storage\StorageClient;
use Google\Cloud\Speech\V1\SpeechClient;
use Google\Cloud\Speech\V1\RecognitionAudio;
use Google\Cloud\Speech\V1\RecognitionConfig;
use Google\Cloud\Speech\V1\RecognitionConfig\AudioEncoding;

putenv('GOOGLE_APPLICATION_CREDENTIALS=/home/donsingh/temp/demo/jarvis.json');

//check for command.wav and trigger transcribe if found
while(1){
	if(file_exists("audio/command.wav")){
		transcribe_file();
		unlink("audio/command.wav");
	}
	sleep(1);
}


//speech to text using google cloud API
function transcribe_file()
{
	$encoding = AudioEncoding::LINEAR16;
	$sampleRateHertz = 44100;
	$languageCode = 'en-US';
	// get contents of a file into a string
	$content = file_get_contents("/home/donsingh/temp/demo/audio/command.wav");
	// set string as audio content
	$audio = (new RecognitionAudio())
	    ->setContent($content);
	// set config
	$config = (new RecognitionConfig())
	    ->setEncoding($encoding)
	    ->setSampleRateHertz($sampleRateHertz)
	    ->setLanguageCode($languageCode);
	// create the speech client
	$client = new SpeechClient();

	try {
	    $response = $client->recognize($config, $audio);
	    foreach ($response->getResults() as $result) {
	        $alternatives = $result->getAlternatives();
	        $mostLikely = $alternatives[0];
	        $transcript = $mostLikely->getTranscript();
	        $confidence = $mostLikely->getConfidence();
	    }
	} finally {
	    $client->close();
		
		//actual process what was said and how to respond
	    if(isset($transcript)){
	        echo $transcript.PHP_EOL;
			if($transcript == "date today"){
				jarvis_say("Today is " . date("F j,Y"));
			}
			if($transcript == "greet everyone"){
				jarvis_say("Maayong Buntag!");
			}
			
			if(strpos($transcript, "greet") !== FALSE){
				$words = explode(" ", $transcript);
				unset($words[0]);
				jarvis_say("Hello " . join(" ", $words));
			}
	    }
	}
}


//text to speech using REST API
function jarvis_say($input)
{
	$url = "https://texttospeech.googleapis.com/v1beta1/text:synthesize?key=AIzaSyDySs-S4U2Zqcdc80Mhy-Nf0fUELXgSkm0";
	$output = "audio/output.mp3";
	
	$options = array(
	    "input" => array(
	        "text" => $input
	    ),
	    "voice" => array(
	        "languageCode" => "en-GB",
	        "name"         => "en-GB-Standard-B",
	        "ssmlGender"   => "MALE"
	    ),
	    "audioConfig" => array(
	        "audioEncoding"   => "MP3",
	        "pitch"           => -2,
	        "sampleRateHertz" => 24000,
	        "speakingRate"    => 0.95
	    )
	);
	
	$curl_pointer = curl_init( $url );
	curl_setopt( $curl_pointer, CURLOPT_POSTFIELDS, json_encode($options) );
	curl_setopt( $curl_pointer, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
	curl_setopt( $curl_pointer, CURLOPT_RETURNTRANSFER, true );
	
	// GO TO GOOGLE NA!
	$result = curl_exec($curl_pointer);
	curl_close($curl_pointer);
	
	$result = json_decode($result);
	file_put_contents($output, base64_decode($result->audioContent, true));

	//speak!
	exec("mpg123 audio/output.mp3 2>&1");

	//delete the file
	unlink($output);
}
