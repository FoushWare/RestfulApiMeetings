<?php

namespace App\Http\Controllers;

use App\Meeting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use JWTAuth;



class MeetingController extends Controller
{

    public function __construct()
    {
        $this->middleware('jwt.auth',['only'=>[
            "store","destroy","update"
        ]]);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $meetings=Meeting::all();

        foreach ($meetings as $meeting){
            $meeting->view_meeting=[
                'href'=>'api/v1/meeting/'.$meeting->id,
                'method'=>'GET'
            ];

        }

        $response=[
            'msg'=>'List of all Meetings',
            'meetings'=>$meeting
        ];

        return response()->json($response,200);

    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {


        /***********validate input*******/
        $this->validate($request,[
           'title'=>'required',
           'time'=>'required',
           'description'=>'required'
        ]);

        /***********Extract Data*******/



            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }





        $title=$request->input('title');
        $description=$request->input('description');
        $time=$request->input('time');
        $user_id=$user->id;

        /***********apply business logic*******/
        $meeting=new Meeting([
            'title'=>$title,
            'description'=>$description,
            'time'=>$time
        ]);


        if($meeting->save()){
            $meeting->users()->attach($user_id); //To add this entiry to the pivate table that has the relationship of the two tables {users,Meetings}
            $meeting=[
                'title'=> $title,
                'description'=>$description,
                'time'=>$time,
                'user_id'=>$user_id,
                'view_meeting'=>[
                    'href'=>'api/v1/meeting/'.$meeting->id,
                    'method'=>'GET'
                ]
            ];

            $response=[
                "msg"=> "Meeting Created successfully",
                "error"=> "0",
                "summary"=>$meeting,
                "meeting_URL"=>"__MEETINGURL__",
                "partcipants"=>[
                    "ahmed","Ali","sayed" ]

            ];
            /***********Response OK *******/

            return response()->json($response,200);


        }

        /***********Response 404 *******/

        $response=[
            'msg'=>'An Error occurred',
            'error'=>'1'

        ];

        return response()->json($response,404);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //Get the Meetings and the related users
       $meeting=Meeting::with('users')->where('id',$id)->findOrFail($id); // if there is not fitting data laravel will send back 404 Page
        $meeting->view_meetings=[
            'href'=>'api/v1/meeting',
            'method'=>'GET'
        ];

        $response=[
          'msg'=>'Meeting info',
          'meeting'=>$meeting
        ];


        return response()->json($response,200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        /***********validate input*******/

        $this->validate($request,[
            'title'=>'required',
            'time'=>'required|date_format:YmdHie',
            'description'=>'required',

        ]);
        /***********Extract Data*******/

        if(! $user=JWTAuth::parseToken()->authenticate()){
            return response()->json(['msg'=>'user not found'],404);
        }


        $title=$request->input('title');
        $time=$request->input('time');
        $description=$request->input('description');
        $user_id=$request->$user->id;
        /***********apply business logic*******/


        $meeting=Meeting::with('users')->findOrFail($id);


        if (!$meeting->users()->where('user_id',$user_id)->first()){

            /***********Response 404 *******/
            return response()->json(['msg'=>'user not registered for meeting ,update not successful'],404);
        }

        $meeting->time=$time;
        $meeting->title=$title;
        $meeting->description=$description;

        if(!$meeting->update()){
            return response()->json(['msg'=>'Error during updating'],404);
        }

        $meeting->view_meeting=[
            'href'=>'api/v1/meeting/'.$meeting->id,
            'method'=>'GET'
        ];
        /***********Response OK*******/
        $response=[
            'msg'=>'Meeting updated',
            'meeting'=>$meeting
        ];

        return response()->json($response,200);


    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $meeting=Meeting::findOrFail($id); //find the Meeting with the $id

        if(! $user=JWTAuth::parseToken()->authenticate()){
            return response()->json(['msg'=>'user not found'],404);
        }

        if (!$meeting->users()->where('user_id',$user->id)->first()){

            /***********Response 404 *******/
            return response()->json(['msg'=>'user not registered for meeting ,update not successful'],404);
        }

        $users=$meeting->users; // find the users that relate to the Meeting to detach them before delete
        $meeting->users()->detach();

        if (!$meeting->delete()){
            foreach ($users as $user) {
                $meeting->users()->attach($user);

            }
            return response()->json(['msg'=>'deletion failed'],404);
        }

        /***********Response*******/
        $response=[
            'msg'=>'Meeting deleted',
            'create'=>[
                'href'=>'api/v1/meeting',
                'method'=>'POST',
                'params'=>'title,description,time'
            ]
        ];

        return response()->json($response,200);




    }
}
