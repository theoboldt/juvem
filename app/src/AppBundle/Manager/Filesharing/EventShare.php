<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace AppBundle\Manager\Filesharing;


class EventShare
{
    /**
     * Name of team share
     *
     * @var string
     */
    private string $nameTeam;
    
    /**
     * Nextcloud file id for team directory
     *
     * @var int
     */
    private int $teamDirectoryId;
    
    /**
     * Name of management share
     *
     * @var string
     */
    private string $nameManagement;
    
    /**
     * Nextcloud file id for management directory
     *
     * @var int
     */
    private int $managementDirectoryId;
    
    /**
     * EventShare constructor.
     *
     * @param string $nameTeam
     * @param int $teamDirectoryId
     * @param string $nameManagement
     * @param int $managementDirectoryId
     */
    public function __construct(
        string $nameTeam, int $teamDirectoryId, string $nameManagement, int $managementDirectoryId
    )
    {
        $this->nameTeam              = $nameTeam;
        $this->teamDirectoryId       = $teamDirectoryId;
        $this->nameManagement        = $nameManagement;
        $this->managementDirectoryId = $managementDirectoryId;
    }
    
    /**
     * @return string
     */
    public function getNameTeam(): string
    {
        return $this->nameTeam;
    }
    
    /**
     * @return int
     */
    public function getTeamDirectoryId(): int
    {
        return $this->teamDirectoryId;
    }
    
    /**
     * @return string
     */
    public function getNameManagement(): string
    {
        return $this->nameManagement;
    }
    
    /**
     * @return int
     */
    public function getManagementDirectoryId(): int
    {
        return $this->managementDirectoryId;
    }
    
    
}