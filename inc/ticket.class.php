<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 vip plugin for GLPI
 Copyright (C) 2016-2022 by the vip Development Team.

 https://github.com/InfotelGLPI/vip
 -------------------------------------------------------------------------

 LICENSE

 This file is part of vip.

 vip is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 vip is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with vip. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginVipTicket extends CommonDBTM {

   static $types = ['Ticket', 'Printer', 'Computer'];

   /**
    * @param $uid
    *
    * @return bool
    */
   static function isUserVip($uid) {
      global $DB;

      $vipquery = "SELECT `glpi_plugin_vip_groups`.`id`
                   FROM `glpi_groups_users`
                   LEFT JOIN `glpi_plugin_vip_groups`
                     ON `glpi_plugin_vip_groups`.`id` = `glpi_groups_users`.`groups_id`
                   WHERE `glpi_plugin_vip_groups`.`isvip` = 1
                   AND `glpi_groups_users`.`users_id` = " . $uid;

      $result = $DB->query($vipquery);
      $nb     = $DB->numrows($result);
      if ($nb > 0) {
         while ($uids = $DB->fetchArray($result)) {
            return $uids['id'];
         }
      }
      return false;
   }

   /**
    * @param $entities
    *
    * @return array
    */
   static function getUserVipList($entities) {
      global $DB;

      $vip      = [];
      $vipquery = "SELECT `glpi_groups_users`.`users_id`
                   FROM `glpi_groups_users`
                   LEFT JOIN `glpi_plugin_vip_groups`
                     ON `glpi_plugin_vip_groups`.`id` = `glpi_groups_users`.`groups_id`
                   WHERE `glpi_plugin_vip_groups`.`isvip` = 1";

      $result = $DB->query($vipquery);
      $nb     = $DB->numrows($result);
      if ($nb > 0) {
         while ($uids = $DB->fetchArray($result)) {
            $vip[] = $uids['users_id'];
         }
      }
      return $vip;
   }

   /**
    * @param $ticketid
    *
    * @return bool
    */
   static function isTicketVip($ticketid) {
      global $DB;

      if ($ticketid > 0) {
         $userquery  = "SELECT `users_id`
                        FROM `glpi_tickets_users`
                        WHERE `type` = " . CommonITILActor::REQUESTER . "
                        AND `tickets_id` = " . $ticketid;
         $userresult = $DB->query($userquery);
         $nb     = $DB->numrows($userresult);
         if ($nb > 0) {
            while ($uids = $DB->fetchArray($userresult)) {
               foreach ($uids as $uid) {
                  $isuservip = self::isUserVip($uid);
                  return $isuservip;
               }
            }
         }
      }
      return false;
   }

   /**
    * @param $printers_id
    *
    * @return bool
    */
   static function isPrinterVip($printers_id) {

      $printer = new Printer();
      $printer->getFromDB($printers_id);
      return self::isUserVip($printer->getField('users_id'));
   }

   /**
    * @param $computers_id
    *
    * @return bool
    */
   static function isComputerVip($computers_id) {

      $computer = new Computer();
      $computer->getFromDB($computers_id);
      return self::isUserVip($computer->getField('users_id'));
   }

   /**
    * @param $params
    *
    * @return void
    */
   public static function showVIPInfos($params) {
      $item = $params['item'];

      if (in_array($item->getType(), self::$types)) {
         if ($item->getType() == 'Ticket') {
            if ($id = self::isTicketVip($item->getID())) {
               $name = PluginVipGroup::getVipName($id);
               $icon = PluginVipGroup::getVipIcon($id);
               echo "<div class='alert alert-important alert-warning center '>";
               echo "<i class='fas $icon fa-2x' title=\"$name\" style='font-family:\"Font Awesome 5 Free\", \"Font Awesome 5 Brands\";'></i>&nbsp;";
               echo sprintf(__('%1$s %2$s'), __('This ticket concerns at least one', 'vip'), $name);
               echo "</div>";
            }
         } else {
            if ($id = self::isUserVip($item->getField('users_id'))) {
               echo "<div class='alert alert-important alert-warning center '>";
               if ($item->getType() == 'Computer') {
                  $name = PluginVipGroup::getVipName($id);
                  $icon = PluginVipGroup::getVipIcon($id);
                  echo "<i class='fas $icon fa-2x' title=\"$name\" style='font-family:\"Font Awesome 5 Free\", \"Font Awesome 5 Brands\";'></i>&nbsp;";
                  echo sprintf(__('%1$s %2$s'), __('This computer is used by a', 'vip'), $name);
               } else if ($item->getType() == 'Printer') {
                  $name = PluginVipGroup::getVipName($id);
                  $icon = PluginVipGroup::getVipIcon($id);
                  echo "<i class='fas $icon fa-2x' title=\"$name\" style='font-family:\"Font Awesome 5 Free\", \"Font Awesome 5 Brands\";'></i>&nbsp;";
                  echo sprintf(__('%1$s %2$s'), __('This printer is used by a', 'vip'), $name);
               }
               echo "</div>";
            }
         }
      }
   }
}
