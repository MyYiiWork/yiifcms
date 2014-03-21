<?php
/**
 * 多文件上传
 * 
 * @author        zhao jinhan <326196998@qq.com>
 * @copyright     Copyright (c) 2014-2015 . All rights reserved.
 *  
 */

class UploadifyController extends Backend
{	
    /**
     * 基本上传
     *
     * @return [type] [description]
     */
    public function actionBasic() {    
        $this->render( 'basic');        
    }

    /**
     * 上传
     */
    public function actionBasicExecute() {     
        if ( $this->method() == 'POST' ) {
            $adminiUserId = Yii::app()->user->id;  
            $file = new XUpload;
            if($this->_request->getParam('from') == 'editor'){
            	//从外部编辑器上传的图片
            	$outside = 'Y';
            	$file->_image_path = 'uploads/attached/'.$this->_request->getParam('dir').'/';
            }                 
            if(is_array($_FILES['imgFile']) && !empty($_FILES['imgFile'])){
            	foreach($_FILES['imgFile'] as $value){
            		if(is_array($value)){
            			$files = $_FILES['imgFile'];
            		}else{
            			$files = array($_FILES['imgFile']);
            		}
            		break;
            	}
            }else{
            	exit( CJSON::encode( array ( 'state' => 'error' , 'message' => Yii::t('admin','Please select a file.') ) ) );
            }    
            foreach($files as $simplefile){
            	$file->uploadFile($simplefile);
            	if($file->_error){
            		exit( CJSON::encode( array ( 'error' => 1 , 'message' => Yii::t('admin',$file->_error) ) ) );
            	}else{
            		$model = new Upload();
            		$model->user_id = intval( $adminiUserId );
            		$model->file_name = $file->_file_name;
            		$model->thumb_name = $file->_thumb_name;
            		$model->real_name = $file->_real_name;
            		$model->file_ext = $file->_file_ext;
            		$model->file_mime = $file->_mime_type;
            		$model->file_size = $file->_file_size;
            		$model->create_time = time();            		
            		if ( $model->save() ) {
            			if($outside == 'Y'){
            				exit(CJSON::encode(array ('error' => 0 , 'url' => Yii::app()->baseUrl . '/' . $file->_file_name )));
            			}else{            				
            				exit( CJSON::encode( array ( 'state' => 'success' , 'fileId'=>$model->id, 'realFile'=>$model->real_name, 'message' => Yii::t('admin','Upload Success') , 'file' =>  $model->file_name ) ) );
            			}
            		} else {
            			$file->deleteFile($file->_file_name);
            			$file->deleteFile($file->_thumb_name);
            			if($outside == 'Y'){
            				exit(CJSON::encode(array ('error' => 1 , 'message' => Yii::t('admin','Save failed, Upload error') )));
            			}else{
            				exit( CJSON::encode( array ( 'state' => 'error' , 'message' => Yii::t('admin','Save failed, Upload error') ) ) );
            			}
            		}
            		
            	}
            }            	
            
        }
    }        
    

    /**
     * 删除附件
     * @return [type] [description]
     */
    public function actionRemove() {
        $imageId = intval( $this->_request->getParam( 'imageId' ) );
        try {
            $imageModel = Upload::model()->findByPk( $imageId );
            if ( $imageModel ==false )
                throw new Exception( "附件已经被删除" );
            @unlink( $imageModel->file_name );
            @unlink( $imageModel->thumb_name );
            if ( !$imageModel->delete() )
                throw new Exception( "数据删除失败" );
            $var['state'] = 'success';
            $var['message'] = '删除完成';
        } catch ( Exception $e ) {
            $var['state'] = 'errro';
            $var['message'] = '失败：'.$e->getMessage();
        }
        exit( CJSON::encode( $var ) );
    }
}