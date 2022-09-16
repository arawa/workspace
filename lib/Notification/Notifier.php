<?php

namespace OCA\Workspace\Notification;

use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class Notifier implements INotifier {
    protected $factory;
    protected $url;

    public function __construct(IFactory $factory, IURLGenerator $URLGenerator)
    {
        $this->factory = $factory;
        $this->url = $URLGenerator;
    }

    /**
     * Identifier of the notifier, only use [a-z0-9_]
     * @return string
     */
    public function getID(): string {
        return 'workspace';
    }

    /**
     * Human readable name describing the notifier
     * @return string
     */
    public function getName(): string {
        return $this->factory->get('workspace')->t('Add workspace');
    }

    /**
     * @param INotification $notification
     * @param string $languageCode The code of the language that should be used to prepare the notification
     */
    public function prepare(INotification $notification, string $languageCode): INotification {
        if ($notification->getApp() !== 'workspace') {
            // Not my app
            throw new \InvalidArgumentException();
        }
        $l = $this->factory->get('workspace', $languageCode);

        $notification->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')))
            ->setLink($this->url->linkToRouteAbsolute('workspace.Page.index', ['id' => $notification->getObjectId()]));

        /**
         * Set rich subject, see https://github.com/nextcloud/server/issues/1706 for mor information
         * and https://github.com/nextcloud/server/blob/master/lib/public/RichObjectStrings/Definitions.php
         * for a list of defined objects and their parameters.
         */
        $parameters = $notification->getSubjectParameters();
        $notification->setRichSubject('You added in the workspace', [
            'workspace' => [
                'type'  => 'user',
                'id'    => 'bstark',
                'name'  => 'Ben Stark'
            ]
        ]);

        return $notification;
    }

}