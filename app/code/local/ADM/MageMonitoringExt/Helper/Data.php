<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Hackathon
 * @package     Hackathon_MageMonitoring
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class ADM_MageMonitoringExt_Helper_Data extends Mage_Core_Helper_Data
{

    /**
     * Retrieve a collection of all events
     *
     * @link https://github.com/firegento/firegento-debug
     * @see  FireGento_Debug_Helper_Firegento::getEventsCollection
     *
     * @return Varien_Data_Collection Collection
     */
    public function getEventsList($codePool)
    {
        $events = $this->_loadEvents($codePool);
        foreach ($events as $eventKey=>$item) {
            $values = $item['children'];
            if (is_array($values)) {
                asort($values);
            }

            $events[$eventKey]['location'] = implode("\n", $values);
        }

        return $events;
    }

    /**
     * Return all events
     *
     * @link https://github.com/firegento/firegento-debug
     * @see  FireGento_Debug_Helper_Firegento::getEventsCollection
     *
     * @return array All events
     */
    protected function _loadEvents($codePool)
    {
        $modules  = Mage::getConfig()->getNode('modules')->children();

        $events = array();
        foreach ($modules as $modName => $module) {
            if((string)$module->codePool!=$codePool) {
                continue;
            }
            if ((string)$module->active=='true') {
                $configFile = Mage::getConfig()->getModuleDir('etc', $modName) . DS . 'config.xml';
                if (file_exists($configFile)) {
                    $xml = file_get_contents($configFile);
                    $xml = simplexml_load_string($xml);

                    if ($xml instanceof SimpleXMLElement) {
                        $events[$modName] = $xml->xpath('//events');
                    }
                }
            }
        }

        $return = array();
        foreach ($events as $module=>$eventNodes) {
            foreach ($eventNodes as $n) {
                $nParent    = $n->xpath('..');
                $nSubParent = $nParent[0]->xpath('..');
                $component  = (string) $nSubParent[0]->getName();
                $pathNodes  = $n->children();

                foreach ($pathNodes as $pathNode) {
                    $eventName = (string) $pathNode->getName();
                    $instance  = $pathNode->xpath('observers/node()/class');
                    $instance  = (string) current($instance);
                    $instance  = Mage::getConfig()->getModelClassName($instance);

                    if (!array_key_exists($eventName, $return)) {
                        $return[$eventName] = array(
                                'event' => $eventName,
                                'module'=> $module,
                                'children' => array()
                        );
                    }
                    if (!in_array($instance, $return[$eventName])) {
                        $return[$eventName]['children'][] = $instance;
                    }
                }
            }
        }

        return $return;
    }
}