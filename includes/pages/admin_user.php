<?php

use Engelsystem\Database\DB;
use Engelsystem\Models\User\User;

/**
 * @return string
 */
function admin_user_title()
{
    return __('All Angels');
}

/**
 * @return string
 */
function admin_user()
{
    $user = auth()->user();
    $tshirt_sizes = config('tshirt_sizes');
    $request = request();
    $html = '';

    if (!$request->has('id')) {
        throw_redirect(users_link());
    }

    $user_id = $request->input('id');
    if (!$request->has('action')) {
        $user_source = User::find($user_id);
        if (!$user_source) {
            error(__('This user does not exist.'));
            throw_redirect(users_link());
        }

        $html .= __('Hello,').'<br />';
        $html .= __('here you can change the entry. Under the item \'Came\' the angel is marked as present, a yes at Active means that the angel was active.');
        if(config('enable_tshirt_size')) {
            $html .=  ' '.__('War der Engel Aktiv, hat er damit Anspruch auf ein T-Shirt. Wenn T-Shirt ein \'Ja\' enth&auml;lt, bedeutet dies, dass der Engel bereits sein T-Shirt erhalten hat.');
        }
        $html .= '<br /><br />';
        $html .= '<form action="'
            . page_link_to('admin_user', ['action' => 'save', 'id' => $user_id])
            . '" method="post">' . "\n";
        $html .= form_csrf();
        $html .= '<table>' . "\n";
        $html .= '<input type="hidden" name="Type" value="Normal">' . "\n";
        $html .= '<tr><td>' . "\n";
        $html .= '<table>' . "\n";
        $html .= '  <tr><td>'.__('Nickname').'</td><td>' . '<input size="40" name="eNick" value="' . $user_source->name . '" class="form-control" maxlength="24"></td></tr>' . "\n";
        $html .= '  <tr><td>'.__('Last Login').'</td><td><p class="help-block">'
            . ($user_source->last_login_at ? $user_source->last_login_at->format('Y-m-d H:i') : '-')
            . '</p></td></tr>' . "\n";
        if (config('enable_user_name')) {
            $html .= '  <tr><td>'.__('Prename').'</td><td>' . '<input size="40" name="eName" value="' . $user_source->personalData->last_name . '" class="form-control" maxlength="64"></td></tr>' . "\n";
            $html .= '  <tr><td>'.__('Last name').'/td><td>' . '<input size="40" name="eVorname" value="' . $user_source->personalData->first_name . '" class="form-control" maxlength="64"></td></tr>' . "\n";
        }
        $html .= '  <tr><td>'.__('Mobile').'</td><td>' . '<input type= "tel" size="40" name="eHandy" value="' . $user_source->contact->mobile . '" class="form-control" maxlength="40"></td></tr>' . "\n";
        if (config('enable_dect')) {
            $html .= '  <tr><td>'.__('DECT').'</td><td>' . '<input size="40" name="eDECT" value="' . $user_source->contact->dect . '" class="form-control" maxlength="40"></td></tr>' . "\n";
        }
        if ($user_source->settings->email_human) {
            $html .= "  <tr><td>'.__('email').'</td><td>" . '<input type="email" size="40" name="eemail" value="' . $user_source->email . '" class="form-control" maxlength="254"></td></tr>' . "\n";
        }
        if(config('enable_tshirt_size')) {
            $html .= '  <tr><td>'.__('user.shirt_size').'</td><td>'
                . html_select_key(
                    'size',
                    'eSize',
                    $tshirt_sizes, $user_source->personalData->shirt_size,
                    __('Please select...')
                )
                . '</td></tr>' . "\n";
        }

        $options = [
            '1' => __('Yes'),
            '0' => __('No')
        ];

        // Gekommen?
        $html .= '  <tr><td>'.__('Arrived').'</td><td>' . "\n";
        if ($user_source->state->arrived) {
            $html .= __('Yes');
        } else {
            $html .= __('No');
        }
        $html .= '</td></tr>' . "\n";

        // Aktiv?
        $html .= '  <tr><td>'.__('user.active').'</td><td>' . "\n";
        $html .= html_options('eAktiv', $options, $user_source->state->active) . '</td></tr>' . "\n";

        // Aktiv erzwingen
        if (auth()->can('admin_active')) {
            $html .= '  <tr><td>' . __('Force active') . '</td><td>' . "\n";
            $html .= html_options('force_active', $options, $user_source->state->force_active) . '</td></tr>' . "\n";
        }

        if(config('enable_tshirt_size')) {
            // T-Shirt bekommen?
            $html .= '  <tr><td>'.__('T-Shirt').'</td><td>' . "\n";
            $html .= html_options('eTshirt', $options, $user_source->state->got_shirt) . '</td></tr>' . "\n";
        }
        $html .= '</table>' . "\n" . '</td><td></td></tr>';

        $html .= '</td></tr>' . "\n";
        $html .= '</table>' . "\n" . '<br />' . "\n";
        $html .= '<input type="submit" value="'.__('form.save').'" class="btn btn-primary">';
        $html .= '</form>';

        $html .= '<hr />';

        $html .= form_info('', __('Please visit the angeltypes page or the users profile to manage users angeltypes.'));

        $html .= __('Here you can reset the password of this angel:').'<form action="'
            . page_link_to('admin_user', ['action' => 'change_pw', 'id' => $user_id])
            . '" method="post">' . "\n";
        $html .= form_csrf();
        $html .= '<table>' . "\n";
        $html .= '  <tr><td>'.__('Password').'</td><td>' . '<input type="password" size="40" name="new_pw" value="" class="form-control"></td></tr>' . "\n";
        $html .= '  <tr><td>'.__('Confirm password').'</td><td>' . '<input type="password" size="40" name="new_pw2" value="" class="form-control"></td></tr>' . "\n";

        $html .= '</table>' . "\n" . '<br />' . "\n";
        $html .= '<input type="submit" value="'.__('form.save').'" class="btn btn-primary">' . "\n";
        $html .= '</form>';

        $html .= '<hr />';

        $my_highest_group = Db::selectOne(
            'SELECT group_id FROM `UserGroups` WHERE `uid`=? ORDER BY `group_id` LIMIT 1',
            [$user->id]
        );
        if (!empty($my_highest_group)) {
            $my_highest_group = $my_highest_group['group_id'];
        }

        $his_highest_group = Db::selectOne(
            'SELECT `group_id` FROM `UserGroups` WHERE `uid`=? ORDER BY `group_id` LIMIT 1',
            [$user_id]
        );
        if (!empty($his_highest_group)) {
            $his_highest_group = $his_highest_group['group_id'];
        }

        if (
            ($user_id != $user->id || auth()->can('admin_groups'))
            && ($my_highest_group <= $his_highest_group || is_null($his_highest_group))
        ) {
            $html .= __('Here you can define the user groups of the angel:').'<form action="'
                . page_link_to('admin_user', ['action' => 'save_groups', 'id' => $user_id])
                . '" method="post">' . "\n";
            $html .= form_csrf();
            $html .= '<table>';

            $groups = Db::select('
                    SELECT *
                    FROM `Groups`
                    LEFT OUTER JOIN `UserGroups` ON (
                        `UserGroups`.`group_id` = `Groups`.`UID`
                        AND `UserGroups`.`uid` = ?
                    )
                    WHERE `Groups`.`UID` >= ?
                    ORDER BY `Groups`.`Name`
                ',
                                 [
                                     $user_id,
                                     $my_highest_group,
                                 ]
            );
            foreach ($groups as $group) {
                $html .= '<tr><td><input type="checkbox" name="groups[]" value="' . $group['UID'] . '" '
                    . ($group['group_id'] != '' ? ' checked="checked"' : '')
                    . ' /></td><td>' . $group['Name'] . '</td></tr>';
            }

            $html .= '</table><br>';

            $html .= '<input type="submit" value="'.__('form.save').'" class="btn btn-primary">' . "\n";
            $html .= '</form>';

            $html .= '<hr />';
        }

        $html .= buttons([
                             button(user_delete_link($user_source->id), icon('trash') . __('delete'), 'btn-danger')
                         ]);

        $html .= "<hr />";
    } else {
        switch ($request->input('action')) {
            case 'save_groups':
                if ($user_id != $user->id || auth()->can('admin_groups')) {
                    $my_highest_group = Db::selectOne(
                        'SELECT * FROM `UserGroups` WHERE `uid`=? ORDER BY `group_id`',
                        [$user->id]
                    );
                    $his_highest_group = Db::selectOne(
                        'SELECT * FROM `UserGroups` WHERE `uid`=? ORDER BY `group_id`',
                        [$user_id]
                    );

                    if (
                        count($my_highest_group) > 0
                        && (
                            empty($his_highest_group)
                            || ($my_highest_group['group_id'] <= $his_highest_group['group_id'])
                        )
                    ) {
                        $groups_source = Db::select('
                                SELECT *
                                FROM `Groups`
                                LEFT OUTER JOIN `UserGroups` ON (
                                    `UserGroups`.`group_id` = `Groups`.`UID`
                                    AND `UserGroups`.`uid` = ?
                                )
                                WHERE `Groups`.`UID` >= ?
                                ORDER BY `Groups`.`Name`
                            ',
                                                    [
                                                        $user_id,
                                                        $my_highest_group['group_id'],
                                                    ]
                        );
                        $groups = [];
                        $grouplist = [];
                        foreach ($groups_source as $group) {
                            $groups[$group['UID']] = $group;
                            $grouplist[] = $group['UID'];
                        }

                        $groupsRequest = $request->input('groups');
                        if (!is_array($groupsRequest)) {
                            $groupsRequest = [];
                        }

                        Db::delete('DELETE FROM `UserGroups` WHERE `uid`=?', [$user_id]);
                        $user_groups_info = [];
                        foreach ($groupsRequest as $group) {
                            if (in_array($group, $grouplist)) {
                                Db::insert(
                                    'INSERT INTO `UserGroups` (`uid`, `group_id`) VALUES (?, ?)',
                                    [$user_id, $group]
                                );
                                $user_groups_info[] = $groups[$group]['Name'];
                            }
                        }
                        $user_source = User::find($user_id);
                        engelsystem_log(
                            'Set groups of ' . User_Nick_render($user_source, true) . ' to: '
                            . join(', ', $user_groups_info)
                        );
                        $html .= success(__('User groups saved.'), true);
                    } else {
                        $html .= error(__('You cannot edit angels with more rights.'), true);
                    }
                } else {
                    $html .= error(__('You cannot edit your own rights.'), true);
                }
                break;

            case 'save':
                $force_active = $user->state->force_active;
                $user_source = User::find($user_id);
                if (auth()->can('admin_active')) {
                    $force_active = $request->input('force_active');
                }
                if ($user_source->settings->email_human) {
                    $user_source->email = $request->postData('eemail');
                }
                $nickValidation = User_validate_Nick($request->postData('eNick'));
                if ($nickValidation->isValid()) {
                    $user_source->name = $nickValidation->getValue();
                }
                $user_source->save();
                if (config('enable_user_name')) {
                    $user_source->personalData->first_name = $request->postData('eVorname');
                    $user_source->personalData->last_name = $request->postData('eName');
                }
                $user_source->personalData->shirt_size = $request->postData('eSize');
                $user_source->personalData->save();
                $user_source->contact->mobile = $request->postData('eHandy');
                $user_source->contact->dect = $request->postData('eDECT');
                $user_source->contact->save();
                $user_source->state->active = $request->postData('eAktiv');
                $user_source->state->force_active = $force_active;
                $user_source->state->got_shirt = $request->postData('eTshirt');
                $user_source->state->save();

                engelsystem_log(
                    'Updated user: ' . $user_source->name . ' (' . $user_source->id . ')'
                    . ', t-shirt: ' . $user_source->personalData->shirt_size
                    . ', active: ' . $user_source->state->active
                    . ', force-active: ' . $user_source->state->force_active
                    . ', tshirt: ' . $user_source->state->got_shirt
                );
                $html .= success(__('Change was saved...') . "\n", true);
                break;

            case 'change_pw':
                if (
                    $request->postData('new_pw') != ''
                    && $request->postData('new_pw') == $request->postData('new_pw2')
                ) {
                    $user_source = User::find($user_id);
                    auth()->setPassword($user_source, $request->postData('new_pw'));
                    engelsystem_log('Set new password for ' . User_Nick_render($user_source, true));
                    $html .= success(__('Password reset done.'), true);
                } else {
                    $html .= error(
                        __('The entries must match and must not be empty!'),
                        true
                    );
                }
                break;
        }
    }

    return page_with_title(__('Edit user'), [
        $html
    ]);
}
