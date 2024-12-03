<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\View;
use App\Models\FRSUser;
use Aws\Rekognition\RekognitionClient;
use Aws\Credentials\Credentials;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;

class AWSController extends Controller
{
    public function index() {
        return View::make('register');
    }

    public function login() {
        return View::make('login');
    }

    public function createClient() {
        $credentials = new Credentials($_ENV['AWS_ACCESS_KEY_ID'], $_ENV['AWS_SECRET_ACCESS_KEY']);
        return RekognitionClient::factory(array(
                    'region'	=> "us-east-1",
                    'version'	=> 'latest',
                'credentials' => $credentials
        ));
    }

    public function indexFace($userId, $imagePath) {
        $imageId = 'user_' . str_pad($userId, 2, '0', STR_PAD_LEFT);
        $result = $this->createClient()->indexFaces([
            'CollectionId' => 'meta-users',
            'DetectionAttributes' => [
            ],
            'ExternalImageId' => $imageId,
            'Image' => [
                'Bytes' => file_get_contents($imagePath),
            ],
        ]);
        return $result;
    }

    public function register(Request $request) {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'mobile' => 'required|unique:frs_user,mobile',
            'webcam' => 'required'
        ],[
            'first_name.required' => 'Please enter first name',
            'mobile.required' => 'Please enter mobile',
            'mobile.unique' => 'Mobile number is already taken',
            'webcam.required' => 'Please select photo',
        ]);
        if ($validator->fails()) {
            return [
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => []
            ];
        }

        $photoName = $request->mobile . '.' . $request->webcam->getClientOriginalExtension();
        $request->webcam->move(public_path('uploads'), $photoName);
        $photoPath = 'uploads/'.$photoName;
        if(empty($request->save_data)) {
            return [
                'status' => true,
                'message' => 'Snapped successfully',
                'data' => [
                    'photo' => $photoPath
                ]
            ];
        }

        DB::beginTransaction();

        $insertArr = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name ?? null,
            'mobile' => $request->mobile,
            'email' => $request->email ?? null,
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ];
        $userId = FRSUser::insertGetId($insertArr);
        if(empty($userId)) {
            return [
                'status' => false,
                'message' => 'Something went wrong. Please try again!!',
                'data' => []
            ];
        }

        try {
            $face = $this->indexFace($userId, public_path($photoPath));
        } catch(Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage(),
                'data' => []
            ];
        }

        if(empty($face['FaceRecords'][0]['Face']['FaceId'])) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'Face did not recognize!!',
                'data' => []
            ];
        }

        $updateArr = [
            'photo' => $photoPath,
            'face_id' => $face['FaceRecords'][0]['Face']['FaceId'],
            'image_id' => $face['FaceRecords'][0]['Face']['ImageId'],
            'aws_face_index_response' => json_encode((array) $face),
        ];
        $update = FRSUser::where('id', $userId)->update($updateArr);
        if(empty($update)) {
            DB::rollBack();
            return [
                'status' => false,
                'message' => 'User details cannot update!!',
                'data' => []
            ];
        }
        DB::commit();

        return [
            'status' => true,
            'message' => 'Registration successful',
            'data' => [
                'photo' => $photoPath
            ]
        ];
    }

    public function loginUser(Request $request) {
        $validator = Validator::make($request->all(), [
            'webcam' => 'required'
        ],[
            'webcam.required' => 'Please select photo'
        ]);
        if ($validator->fails()) {
            return [
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => []
            ];
        }

        $photoName = $request->mobile . '-compare.' . $request->webcam->getClientOriginalExtension();
        $request->webcam->move(public_path('uploads'), $photoName);
        $photoPath = 'uploads/'.$photoName;
        if(empty($request->save_data)) {
            return [
                'status' => true,
                'message' => 'Snapped successfully',
                'data' => [
                    'photo' => $photoPath
                ]
            ];
        }

        try {
            $face = $this->searchFace($photoPath);
        } catch(Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage(),
                'data' => []
            ];
        }
        
        if(empty($face['SearchedFaceConfidence']) || empty($face['FaceMatches']) || $face['SearchedFaceConfidence'] < 95) {
            return [
                'status' => false,
                'message' => 'Unauthorized!!',
                'data' => []
            ];
        }

        $faceId = null;
        $similarity = 0;
        foreach($face['FaceMatches'] as $faceMatch) {
            if(!empty($faceMatch['Similarity']) && $faceMatch['Similarity'] > 95 && $faceMatch['Similarity'] > $similarity) {
                $faceId = $faceMatch['Face']['FaceId'];
                $similarity = $faceMatch['Similarity'];
            }
        }

        if(empty($faceId)) {
            return [
                'status' => false,
                'message' => 'Unauthorized!!',
                'data' => []
            ];
        }

        $user = FRSUser::where('face_id', $faceId)->get();
        if(empty($user->count())) {
            return [
                'status' => false,
                'message' => 'No face matched!!',
                'data' => []
            ];
        } else if($user->count() > 1) {
            return [
                'status' => false,
                'message' => 'Duplicate faces!!',
                'data' => []
            ];
        }

        $userDetails = $user->first();
        Session::put('current_user', $userDetails->toArray());

        $updateArr = [
            'last_search_response' => json_encode((array) $face),
            'last_search_confidence' => $face['SearchedFaceConfidence']
        ];
        FRSUser::where('id', $userDetails->id)->update($updateArr);

        return [
            'status' => true,
            'message' => 'Logged-In successfully',
            'data' => [
                'photo' => $photoPath
            ]
        ];
    }

    public function checkFaceIdExist($faceId) {
        return FRSUser::where('face_id', $faceId)->count() >= 1;
    }
    
    public function searchFace($imagePath) {
        $result = $this->createClient()->searchFacesByImage([
            'CollectionId' => 'meta-users',
            'FaceMatchThreshold' => 95,
            'Image' => [
                'Bytes' => file_get_contents($imagePath),
            ],
            'MaxFaces' => 20,
        ]);

        return $result;
    }

    public function welcome(Request $request) {
        if(!empty($request->logout)) {
            Session::flush();
            return redirect(route('login'));
        }
        return View::make('welcome');
    }
}
