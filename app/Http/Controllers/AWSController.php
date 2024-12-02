<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\View;
use App\Models\FRSUser;
use Aws\Rekognition\RekognitionClient;
use Aws\Credentials\Credentials;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
            'mobile' => 'required|unique:frs_user,mobile'
        ]);
        if ($validator->fails()) {
            return json_encode([
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => []
            ]);
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
            return json_encode([
                'status' => false,
                'message' => 'Something went wrong. Please try again!!',
                'data' => []
            ]);
        }

        $face = $this->indexFace($userId, public_path($photoPath));

        if(empty($face['FaceRecords'][0]['Face']['FaceId'])) {
            DB::rollBack();
            return json_encode([
                'status' => false,
                'message' => 'Face did not recognize!!',
                'data' => []
            ]);
        }

        $updateArr = [
            'photo' => $photoPath,
            'face_id' => $face['FaceRecords'][0]['Face']['FaceId'],
            'image_id' => $face['FaceRecords'][0]['Face']['ImageId'],
            'aws_face_index_response' => json_encode($face['FaceRecords']),
        ];
        $update = FRSUser::where('id', $userId)->update($updateArr);
        if(empty($update)) {
            DB::rollBack();
            return json_encode([
                'status' => false,
                'message' => 'User details cannot update!!',
                'data' => []
            ]);
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
}
