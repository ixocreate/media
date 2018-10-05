<?php
declare(strict_types=1);

namespace KiwiSuite\Media;

/** @var PipeConfigurator $pipe */
use KiwiSuite\Admin\Config\AdminConfig;
use KiwiSuite\ApplicationHttp\Pipe\GroupPipeConfigurator;
use KiwiSuite\ApplicationHttp\Pipe\PipeConfigurator;
use KiwiSuite\Media\Action\Image\EditorAction;
use KiwiSuite\Media\Action\Image\ImageDefinitionDetailAction;
use KiwiSuite\Media\Action\Image\ImageDefinitionListAction;
use KiwiSuite\Media\Action\Media\ChangePublicStatusAction;
use KiwiSuite\Media\Action\Media\DeleteAction;
use KiwiSuite\Media\Action\Media\DetailAction;
use KiwiSuite\Media\Action\Media\EditAction;
use KiwiSuite\Media\Action\Media\FilterAction;
use KiwiSuite\Media\Action\Media\IndexAction;
use KiwiSuite\Media\Action\UploadAction;

$pipe->segmentPipe(AdminConfig::class)(function(PipeConfigurator $pipe) {
    $pipe->segment('/api')( function(PipeConfigurator $pipe) {

        $pipe->group("admin.authorized")(function (GroupPipeConfigurator $group) {
            $group->get('/media', IndexAction::class, 'admin.api.media.index');
            $group->get('/media/{id}', DetailAction::class, 'admin.api.media.detail');
            $group->post('/media/editor', EditorAction::class, 'admin.api.media.editor');
            $group->post('/media/upload', UploadAction::class, 'admin.api.media.upload');
            $group->patch('/media/public/{id}', ChangePublicStatusAction::class, 'admin.api.media.publicStatus');
            $group->delete('/media/{id}', DeleteAction::class, 'admin.api.media.delete');
        });
    });
});


