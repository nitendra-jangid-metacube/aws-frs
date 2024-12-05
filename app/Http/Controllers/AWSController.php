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
use Illuminate\Support\Str;
use App\Models\FRSUserFace;

use function PHPUnit\Framework\isEmpty;

class AWSController extends Controller
{
    private $collectionId = 'meta-users';
    public function index() {
        return View::make('register');
    }

    public function login() {
        if(!empty(Session::get('current_user'))) {
            return redirect(route('welcome'));
        }
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

    public function indexFaces($userId, $imagePath) {
        $imageId = 'user_' . str_pad($userId, 2, '0', STR_PAD_LEFT);
        $result = $this->createClient()->indexFaces([
            'CollectionId' => $this->collectionId,
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

        if(Session::has('user_photo') && !empty(Session::get('user_photo')) && count(Session::get('user_photo')) >= 5 && empty($request->save_data)) {
            return [
                'status' => false,
                'message' => 'Max 5 snaps are allowed',
                'data' => []
            ];
        }

        if(empty($request->save_data)) {
            $photoName = rand(10,100) . '_' . bin2hex(random_bytes(2)) . '.' . $request->webcam->getClientOriginalExtension();
            $request->webcam->move(public_path('uploads'), $photoName);
            $photoPath = 'uploads/'.$photoName;

            if(Session::has('user_photo')) {
                Session::push('user_photo', $photoPath);
            } else {
                Session::put('user_photo', [$photoPath]);
            }

            return [
                'status' => true,
                'message' => 'Snapped successfully',
                'data' => [
                    'photo' => $photoPath
                ]
            ];
        }
        $userPhotos = Session::get('user_photo');
        if(empty($userPhotos) || count($userPhotos) < 4) {
            return [
                'status' => false,
                'message' => 'Min 4 snaps are required',
                'data' => []
            ];
        }

        Session::flush();

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
        $insertUserFaceArr = [];
        foreach($userPhotos as $userPhoto) {
            try {
                $face = $this->indexFaces($userId, public_path($userPhoto));
            } catch(Exception $e) {
                return [
                    'status' => false,
                    'message' => $e->getMessage(),
                    'data' => []
                ];
            }
            if(empty($face['FaceRecords'][0]['Face']['FaceId']) || empty($face['FaceRecords'][0]['FaceDetail']['Confidence']) || $face['FaceRecords'][0]['FaceDetail']['Confidence'] < 90) {
                DB::rollBack();
                return [
                    'status' => false,
                    'message' => 'Face did not recognize!!',
                    'data' => []
                ];
            }
            $insertUserFaceArr[] = [
                'user_id' => $userId,
                'face_id' => $face['FaceRecords'][0]['Face']['FaceId'],
                'image_id' => $face['FaceRecords'][0]['Face']['ImageId'],
                'face_index_confidence' => $face['FaceRecords'][0]['FaceDetail']['Confidence'],
                'aws_face_index_response' => json_encode((array) $face),
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'photo' => $userPhoto
            ];
        }
        $insertUserFace = FRSUserFace::insert($insertUserFaceArr);
            if(empty($insertUserFace)) {
                DB::rollBack();
                return [
                    'status' => false,
                    'message' => 'Cannot insert user face!!',
                    'data' => []
                ];
            }
        
        DB::commit();

        return [
            'status' => true,
            'message' => 'Registration successful',
            'data' => [
                'photo' => end($userPhotos)
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

        $photoName = rand(10,100) . '_' . bin2hex(random_bytes(2)) . '-compare.' . $request->webcam->getClientOriginalExtension();
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

        $user = FRSUser::whereHas('face', function($query) use ($faceId) {
            return $query->where('face_id', $faceId);
        })->get();
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
        $frsUserFace = FRSUserFace::where('face_id', $faceId)->first();
        $currentUser = [
            'first_name' => $userDetails->first_name,
            'last_name' => $userDetails->last_name,
            'photo' => $frsUserFace->photo,
        ];
        Session::put('current_user', $currentUser);

        $updateArr = [
            'last_search_confidence' => $face['SearchedFaceConfidence'],
            'last_search_response' => json_encode((array) $face),
        ];
        FRSUserFace::where('id', $frsUserFace->id)->update($updateArr);

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
            'CollectionId' => $this->collectionId,
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

    public function removeSnap(Request $request) {
        $validator = Validator::make($request->all(), [
            'path' => 'required'
        ],[
            'path.required' => 'Invalid request'
        ]);
        if ($validator->fails()) {
            return [
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => []
            ];
        }
        $userPhotos = Session::get('user_photo');
        if(!in_array($request->path, $userPhotos))  {
            return [
                'status' => false,
                'message' => 'Invalid face',
                'data' => []
            ];
        }
        $key = array_search($request->path, $userPhotos);
        unset($userPhotos[$key]);
        Session::put('user_photo', $userPhotos);
        return [
            'status' => true,
            'message' => 'Face removed successfully',
            'data' => []
        ];
    }
}
