<?php

declare(strict_types=1);

/*
 * This file is part of the Slim 4 PHP application.
 *
 * (ɔ) Frugan <dev@frugan.it>
 *
 * This source file is subject to the GNU GPLv3 license that is bundled
 * with this source code in the file COPYING.
 */

namespace App\Controller\Mod\Formvalue\Front;

use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Response;
use Symfony\Component\EventDispatcher\GenericEvent;

trait FormvalueEventTrait
{
    public function eventGetOneSelect(GenericEvent $event): void
    {
        parent::eventGetOneSelect($event);

        $this->dbData['sql'] .= ', CONCAT(e.lastname, " ", e.firstname) AS member_lastname_firstname';
        $this->dbData['sql'] .= ', g.code AS catform_code';
        $this->dbData['sql'] .= ', m.option AS formfield_option';

        if ($this->rbac->isGranted($this->modName.'.'.static::$env.'.edit')) {
            $this->dbData['sql'] .= ', g.sdate AS catform_sdate';
            $this->dbData['sql'] .= ', g.edate AS catform_edate';
            $this->dbData['sql'] .= ', g.maintenance AS catform_maintenance';

            if (\in_array($this->auth->getIdentity()['_type'], ['member'], true)) {
                // https://stackoverflow.com/a/52175323/3929620
                $this->dbData['sql'] .= ', CASE WHEN JSON_VALID(a.data)
            THEN JSON_CONTAINS_PATH(a.data, "one", CONCAT(SUBSTRING_INDEX(JSON_UNQUOTE(JSON_SEARCH(a.data, "one", :email)), ".", 3), ".status"))
            ELSE 0
        END AS active';
                $this->dbData['args']['email'] = $this->auth->getIdentity()['email'];
            } else {
                $this->dbData['sql'] .= ', CASE WHEN JSON_VALID(a.data)
            THEN JSON_LENGTH(JSON_EXTRACT(a.data, "$.teachers.*.status")) = JSON_LENGTH(JSON_EXTRACT(a.data, "$.teachers"))
            ELSE 0
        END AS active';
            }
        }
    }

    public function eventGetOneJoin(GenericEvent $event): void
    {
        parent::eventGetOneJoin($event);

        $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].'member AS e
        ON a.member_id = e.id';
        $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].'catform AS g
        ON a.catform_id = g.id';
        $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].'formfield AS m
        ON a.formfield_id = m.id';
    }

    public function eventGetAllSelect(GenericEvent $event): void
    {
        parent::eventGetAllSelect($event);

        $this->dbData['sql'] .= ', a.catform_id';
        $this->dbData['sql'] .= ', a.form_id';
    }

    public function _eventActionAddAfter(GenericEvent $event): void
    {
        if ($this->auth->hasIdentity()) {
            if (!empty($this->cache->taggable)) {
                $tags = [
                    'local-'.$this->auth->getIdentity()['_type'].'-'.$this->auth->getIdentity()['id'],
                    'global-1',
                ];

                $this->cache->invalidateTags($tags);
            } else {
                $this->cache->clear();
            }
        }
    }

    public function _eventActionEditAfter(GenericEvent $event): void
    {
        // student email
        $ModMember = $this->container->get('Mod\Member\\'.ucfirst(static::$env));

        $oldViewLayoutRegistryPaths = $this->viewLayoutRegistryPaths;
        $oldViewRegistryPaths = $this->viewRegistryPaths;
        $oldViewLayout = $this->viewLayout;

        $env = 'email';

        array_push(
            $this->viewLayoutRegistryPaths,
            _ROOT.'/app/view/'.$env.'/layout',
            _ROOT.'/app/view/'.$env.'/partial'
        );

        array_push(
            $this->viewRegistryPaths,
            _ROOT.'/app/view/'.$env.'/controller/'.$this->controller,
            _ROOT.'/app/view/'.$env.'/base',
            _ROOT.'/app/view/'.$env.'/partial'
        );

        $this->viewLayout = 'blank';

        $ModMember->setId($this->member_id);

        if ($ModMember->exist()) {
            $ModMember->setFields();

            $this->viewData = array_merge(
                $this->viewData,
                [
                    'Mod' => $ModMember,
                ]
            );
        } else {
            throw new HttpNotFoundException($this->container->get('request'));
        }

        $subject = $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('%1$s %2$s at %3$s'), __('New'), $this->helper->Nette()->Strings()->lower(__('Recommendation letters')), settingOrConfig('company.supName')));

        $html = $this->renderBody($this->container->get('request'), new Response(), []);

        [, $emailDomain] = explode('@', (string) $ModMember->email);

        $params = [
            'to' => $ModMember->email,
            'sender' => $this->config['mail.'.$ModMember->controller.'.sender'] ?? null,
            'from' => $this->config['mail.'.$ModMember->controller.'.from'] ?? null,
            'replyTo' => $this->config['mail.'.$ModMember->controller.'.replyTo'] ?? null,
            'cc' => $this->config['mail.'.$ModMember->controller.'.cc'] ?? null,
            'bcc' => $this->config['mail.'.$ModMember->controller.'.'.$emailDomain.'.bcc'] ?? $this->config['mail.'.$emailDomain.'.bcc'] ?? $this->config['mail.'.$ModMember->controller.'.bcc'] ?? null,
            'returnPath' => $this->config['mail.'.$ModMember->controller.'.returnPath'] ?? null,
            'subject' => $subject,
            'html' => $html,
            'text' => $this->helper->Html()->html2Text($html),
        ];

        $this->mailer->prepare($params);

        if (!$this->mailer->send()) {
            $this->errors[] = __('A technical problem has occurred, try again later.');
        }

        $this->viewLayoutRegistryPaths = $oldViewLayoutRegistryPaths;
        $this->viewRegistryPaths = $oldViewRegistryPaths;
        $this->viewLayout = $oldViewLayout;
    }
}
