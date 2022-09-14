<?php

use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

namespace OCA\Workspace\Notification;

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

        // Read the language from the notification
        $l = $this->factory->get('workspace', $languageCode);

        switch ($notification->getSubject()) {
            // Deal with know subjects
            case 'add_user_in_group':
                try {
                    $this->shareManager->getShareById($notification->getObjectId(), $notification->getUser());

                } catch (Exception $e) {
                    // Throw Exception exception when the notification has already been solved and can be removed
                    throw new Exception();
                }

                $notification->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/share.svg')))
                    ->setLink($this->url->linkToRouteAbsolute('files_sharing.RemoteShare.overview', ['id' => $notification->getObjectId()]));

                /**
                 * Set rich subject, see https://github.com/nextcloud/server/issues/1706 for mor information
                 * and https://github.com/nextcloud/server/blob/master/lib/public/RichObjectStrings/Definitions.php
                 * for a list of defined objects and their parameters.
                 */
                $parameters = $notification->getSubjectParameters();
                $notification->setRichSubject($l->t('You added in the workspace "{workspace}"'), [
                    'workspace' => [
                        'type'  => 'adding-workspace',
                        'id'    => $notification->getObjectId(),
                        'name'  => $parameters['name'],
                    ]
                ]);

                // Deal with the actions for a know subject
                foreach ($notification->getActions() as $action) {
                    switch ($action->getLabel()) {
                        case 'accept':
                            $action->setParsedLabel($l->t('Accept'))
                                ->setLink($this->url->linkToRouteAbsolute('files_sharing.RemoteShare.accept', ['id' => $notification->getObjectId()]), 'POST');
                            
                            break;

                        case 'decline':
                            $action->setParsedLabel($l->t('Decline'))
                                ->setLink($this->url->linkToRouteAbsolute('files_sharing.RemoteShare.decline', ['id' => $notification->getObjectId()]), 'DELETE');
                            
                            break;
                    }

                    $notification->addParsedAction($action);
                }

                // Set the plain text subject automatically
                $this->setParsedSubjectFromRichSubject($notification);
                return $notification;

            default:
                // Unknow subject => Unknown notification
                throw new \InvalidArgumentException();
        }
    }

    /**
     * This is a little helper function which automatically sets the simple parsed subject
     * based on the rich subject you set.
     */
    protected function setParsedSubjectFromRichSubject(INotification $notification) {
        $placeholders = $replacements = [];

        foreach ($notification->getRichSubjectParameters() as $placeholder => $parameter) {
            $placeholders[] = '{' . $placeholder . '}';

            if ($parameter['type'] === 'file') {
                $replacements[] = $parameter['path'];
            } else {
                $replacements[] = $parameter['name'];
            }

            $notification->setParsedSubject(str_replace($placeholders, $replacements, $notification->getRichSubject()));
        }
    }
}