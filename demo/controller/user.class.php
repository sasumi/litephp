<?php
class Controller_User extends Controller_Base {
	public function update($req=array(), $data=array()){
		if(!empty($data)){
			$user = Model_User::find('id=?', $req['id'])->one();
			$user->name = $data['name'];
			$result = $user->save();
			
			//update
			return new CResult($user, 'update', $result);
		}
	}
	
	public function add($request_data = array()){
	
	}
}