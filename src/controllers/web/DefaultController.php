<?php

namespace portalium\storage\controllers\web;

use Yii;
use yii\web\UploadedFile;
use yii\filters\VerbFilter;
use portalium\web\Controller;
use yii\web\NotFoundHttpException;
use portalium\storage\models\Storage;
use portalium\storage\models\StorageSearch;
use portalium\storage\Module;
use portalium\storage\widgets\FilePicker;

/**
 * StorageController implements the CRUD actions for Storage model.
 */
class DefaultController extends Controller
{
    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'verbs' => [
                    'class' => VerbFilter::className(),
                    'actions' => [
                        'delete' => ['POST'],
                    ],
                ],
            ]
        );
    }

    /**
     * Lists all Storage models.
     *
     * @return string
     */
    public function actionIndex()
    {
        if (!Yii::$app->user->can('storageWebDefaultIndex')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
        $searchModel = new StorageSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Storage model.
     * @param int $id_storage Id Storage
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id_storage)
    {
        if (!Yii::$app->user->can('storageWebDefaultView')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
        return $this->render('view', [
            'model' => $this->findModel($id_storage),
        ]);
    }

    /**
     * Creates a new Storage model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        if (!Yii::$app->user->can('storageWebDefaultCreate')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
        $model = new Storage();
        if($this->request->isAjax){
            if ( $this->request->post('id_storage') != 'null' ) {
                return $this->updatePjax($this->request->post('id_storage'));
            }
            $file = UploadedFile::getInstanceByName('file');
            if($file){
                $fileName = md5(rand()) . '.' . $file->extension;
                if($file->saveAs(Yii::$app->basePath . Yii::$app->setting->getValue('app::data') . $fileName)){
                    $model->name = $fileName;
                    $model->title = $this->request->post('title');
                    $model->id_user = Yii::$app->user->id;
                    if($model->save()){
                        return json_encode(['name' => $fileName]);
                    }
                }else{
                    return "error";
                }
            }
        }
        if ($this->request->isPost) {
            if ($model->load($this->request->post())) {
                $model->file = UploadedFile::getInstance($model, 'file');
                if ($model->upload()) {
                    \Yii::$app->session->addFlash('success', Module::t('File uploaded successfully'));
                    return $this->redirect(['view', 'id_storage' => $model->id_storage]);
                }else{
                    \Yii::$app->session->addFlash('error', Module::t('Error uploading file</br>Allowed file types: {types}', ['types' => $model->getAllowedExtensions()]));
                   return $this->render('create', [
                        'model' => $model,
                    ]);
                }
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Storage model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id_storage Id Storage
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id_storage)
    {
        if (!Yii::$app->user->can('storageWebDefaultUpdate')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
        $model = $this->findModel($id_storage);
        if ($this->request->isPost && $model->load($this->request->post())) 
        {
            $model->file = UploadedFile::getInstance($model, 'file');
            if ($model->file){
                $model->deleteFile($model->name);
            }
            if ($model->upload()) {

                \Yii::$app->session->addFlash('success', Module::t('File uploaded successfully'));
                return $this->redirect(['view', 'id_storage' => $model->id_storage]);
            }else{
                \Yii::$app->session->addFlash('error', Module::t('Error uploading file'));
                \Yii::$app->session->addFlash('error', Module::t('Error uploading file</br>Allowed file types: {types}', ['types' => $model->getAllowedExtensions()]));
                return $this->render('update', [
                    'model' => $model,
                ]);
            }
        }   

        return $this->render('update', [
            'model' => $model,
        ]);
    }
    
    protected function updatePjax($id_storage)
    {
            $model = $this->findModel($id_storage);
            $model->title = $this->request->post('title');
            $model->file = UploadedFile::getInstanceByName('file');
            if ($model->file){
                $model->deleteFile($model->name);
            }
            if ($model->upload()) {
                return json_encode(['name' => $model->name]);
            }else{
               return json_encode(['error' => Module::t('Error uploading file')]);
            }
    }

    /**
     * Deletes an existing Storage model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id_storage Id Storage
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id_storage)
    {
        if (!Yii::$app->user->can('storageWebDefaultDelete')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
        $model = $this->findModel($id_storage);
        $model->deleteFile($model->name);
        $model->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Storage model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id_storage Id Storage
     * @return Storage the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id_storage)
    {
        if (($model = Storage::findOne(['id_storage' => $id_storage])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Module::t('The requested page does not exist.'));
    }

}
