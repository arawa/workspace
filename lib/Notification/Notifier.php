<?php

namespace OCA\Workspace\Notification;

use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\IAction;
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

        $notification->setIcon($this->url->getAbsoluteURL($this->url->imagePath('workspace', 'Workspace.svg')));
        //     ->setLink($this->url->linkToRouteAbsolute('workspace.Page.index'));

        // $action = $notification->createAction();
        // $action->setLabel('View workspace')
        //     ->setParsedLabel('View workspace')
        //     ->setLink($notification->getLink(), IAction::TYPE_WEB)
        //     ->setPrimary(false);
        // $notification->addParsedAction($action);

        /**
         * Set rich subject, see https://github.com/nextcloud/server/issues/1706 for mor information
         * and https://github.com/nextcloud/server/blob/master/lib/public/RichObjectStrings/Definitions.php
         * for a list of defined objects and their parameters.
         */
        // $parameters = $notification->getSubjectParameters();
        $notification->setRichSubject($l->t('You added in the workspace'), [
            'workspace' => [
                'type'  => 'highlight',
                'id'    => 'bstark',
                'name'  => 'Ben Stark'
            ]
        ]);

        $notification->setParsedSubject($l->t('You added in the workspace'));

        return $notification;
    }

}