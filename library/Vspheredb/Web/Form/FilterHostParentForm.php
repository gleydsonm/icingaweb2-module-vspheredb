<?php

namespace Icinga\Module\Vspheredb\Web\Form;

use dipl\Html\Form;
use dipl\Translation\TranslationHelper;
use Icinga\Module\Vspheredb\Db;

class FilterHostParentForm extends Form
{
    use TranslationHelper;

    protected $db;

    public function __construct(Db $connection)
    {
        $this->db = $connection->getDbAdapter();
        $this->setMethod('GET');
    }

    protected function assemble()
    {
        $vMotionEvents = [
            // 'MigrationEvent',
            'VmBeingMigratedEvent',
            'VmBeingHotMigratedEvent',
            'VmEmigratingEvent',
            'VmMigratedEvent',
            'VmFailedMigrateEvent',
        ];

        $otherKnownEvents = [
            'VmStartingEvent',
            'VmPoweredOnEvent',
            'VmStoppingEvent',
            'VmPoweredOffEvent',
            'VmResettingEvent',
            'VmBeingCreatedEvent',
            'VmCreatedEvent',
            'VmReconfiguredEvent',
            'VmSuspendedEvent',
            'VmBeingDeployedEvent',
            'VmBeingClonedEvent',
            'VmBeingClonedNoFolderEvent',
            'VmClonedEvent',
            'VmCloneFailedEvent'
        ];

        $this->addElement('type', 'select', [
            'options' => [
                null => $this->translate('- filter -')
            ] + array_combine($vMotionEvents, $vMotionEvents)
                + array_combine($otherKnownEvents, $otherKnownEvents),
            'class' => 'autosubmit',
        ]);
        $this->addElement('parent', 'select', [
            'options' => [
                    null => $this->translate('- filter -')
                ] + $this->enumHostParents(),
            'class' => 'autosubmit',
        ]);
    }

    public function getColors()
    {
        $colors = [
            'VmPoweredOffEvent' => [255, 0, 0],
            'VmResettingEvent' => [164, 0, 0],
            'VmBeingHotMigratedEvent' => [255, 164, 0],
            'VmReconfiguredEvent' => [164, 0, 128],
            'VmPoweredOnEvent' => [0, 164, 0],
            'VmCreatedEvent' => [0, 164, 0],
            'VmStartingEvent' => [119, 170, 255],
            'VmBeingCreatedEvent' => [119, 170, 255],
        ];

        $type = $this->getValue('type');
        if (isset($colors[$type])) {
            return $colors[$type];
        } else {
            return $colors['VmReconfiguredEvent'];
        }
    }

    protected function enumHostParents()
    {
        $db = $this->db;
        $query = $db->select()->from(
            ['p' => 'object'],
            ['p.uuid', 'p.object_name']
        )->join(
            ['c' => 'object'],
            'c.parent_uuid = p.uuid AND '
            . $db->quoteInto('c.object_type = ?', 'HostSystem')
            . ' AND '
            . $db->quoteInto('p.object_type = ?', 'ClusterComputeResource'),
            []
        )->group('p.uuid')->order('p.object_name');

        $enum = [];
        foreach ($db->fetchPairs($query) as $k => $v) {
            $enum[bin2hex($k)] = $v;
        }

        return $enum;
    }

    /**
     * Hint: this is required unless we're using a Director release
     * including 095de49
     *
     * @param \Icinga\Web\Request $request
     * @return $this
     */
    public function setRequest($request)
    {
        if ($this->getAction() === null) {
            $this->setAction($request->getUrl()->getAbsoluteUrl('&'));
        }
        parent::setRequest($request);

        return $this;
    }
}
