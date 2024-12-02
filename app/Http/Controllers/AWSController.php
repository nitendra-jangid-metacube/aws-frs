<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Aws\Rekognition\RekognitionClient;
use Aws\Credentials\Credentials;
use Exception;

class AWSController extends Controller
{
    public function index() {
        
        $credentials = new Credentials($_ENV['AWS_ACCESS_KEY_ID'], $_ENV['AWS_SECRET_ACCESS_KEY']);
        $rekognitionClient = RekognitionClient::factory(array(
                    'region'	=> "us-east-1",
                    'version'	=> 'latest',
                'credentials' => $credentials
        ));

        // start create collection
       /*  $result = $rekognitionClient->createCollection([
            'CollectionId' => 'meta-users'
        ]);

        print_r($result);
        die; */
        // end create collection

        //start Rekognition Access
       /*  try {
            $compareFaceResults= $rekognitionClient->compareFaces([
                    'SimilarityThreshold' => 80,
                    'SourceImage' => [
                        'Bytes' => file_get_contents("first.jpg")
                    ],
                    'TargetImage' => [
                        'Bytes' => file_get_contents("second.jpg")
                    ],
            ]);
            
            echo "<pre>";
            print_r($compareFaceResults);
            echo "</pre>";die;
            
            //Response to JSON Data
            // $FaceMatchesResult = $compareFaceResults['FaceMatches'];
            // $SimilarityResult =  $FaceMatchesResult['Similarity']; //Here You will get similarity
            // $sourceImageFace = $compareFaceResults['SourceImageFace'];
            // $sourceConfidence = $sourceImageFace['Confidence']; //Here You will get confidence of the picture
                
        } catch(Exception $e){
            echo $e->getMessage();
        } */
        //end Rekognition Access

        // start index_faces into collection to add face in collection

       /*  $result = $rekognitionClient->indexFaces([
            'CollectionId' => 'meta-users',
            'DetectionAttributes' => [
            ],
            'ExternalImageId' => 'user_01',
            'Image' => [
                'Bytes' => file_get_contents("second.jpg"),
            ],
        ]);

        print_r($result); */

        // end index_faces into collection to add face in collection


        // start get all collections

        /* $result = $rekognitionClient->listCollections([
        ]);
        
        print_r($result); */

        // end get all collections
        
        // start To search for faces matching a supplied image

        /* $result = $rekognitionClient->searchFacesByImage([
            'CollectionId' => 'meta-users',
            'FaceMatchThreshold' => 95,
            'Image' => [
                'Bytes' => file_get_contents("second.jpg"),
            ],
            'MaxFaces' => 5,
        ]);

        print_r($result); */

        // end To search for faces matching a supplied image
    }
}
