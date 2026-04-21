<?php

/**
 * Registry of identifiers used in the project
 */
class ProjectBuildData implements JsonSerializable
{
    private Lump_Registry $lumpRegistry;
    /**
     * @var ReservedIdentifier[]
     */
    private array $doomEdNums;

    /**
     * @var ReservedIdentifier[]
     */
    private array $spawnNums;

    /**
     * @var RejectedIdentifier[]
     */
    private array $rejectedDoomEdNums;

    /**
     * @var RejectedIdentifier[]
     */
    private array $rejectedSpawnNums;

    /**
     * @var int[]
     */
    private array $rampIdsWithRejectedScripts;

    public function __construct(Lump_Registry $lumpRegistry) {
        $this->lumpRegistry = $lumpRegistry;
        $this->doomEdNums = [];
        $this->spawnNums = [];
        $this->rejectedDoomEdNums = [];
        $this->rejectedSpawnNums = [];
        $this->rampIdsWithRejectedScripts = [];
    }

    private function addDoomEdNum($number, $rampId, $className) {
        $this->doomEdNums[$number] = new ReservedIdentifier($number, $rampId, $className);
    }

    private function addSpawnNum($number, $rampId, $className) {
        $this->spawnNums[$number] = new ReservedIdentifier($number, $rampId, $className);
    }

    private function addRejectedDoomEdNum($number, $owningRampId, $attemptingRampId, $className) {
        $this->rejectedDoomEdNums[$number] = new RejectedIdentifier($number, $owningRampId, $attemptingRampId, $className);
    }

    private function addRejectedSpawnNum($number, $owningRampId, $attemptingRampId, $className) {
        $this->rejectedSpawnNums[$number] = new RejectedIdentifier($number, $owningRampId, $attemptingRampId, $className);
    }

    public function addRejectedScript($attemptingRampId) {
        $this->rampIdsWithRejectedScripts[] = $attemptingRampId;
    }

    public function getDoomEdNum($number) {
        return $this->doomEdNums[$number] ?? null;
    }

    public function getSpawnNum($number) {
        return $this->spawnNums[$number] ?? null;
    }

    public function reserveDoomEdNumber($number, $rampId, $className): bool
    {
        $detected_doomed_num_range = $this->lumpRegistry->getRangeForDoomEdNum($number);
        if ($detected_doomed_num_range) {
            Logger::pg("❌ DoomedNum conflict: " . $number . " for " . $className . " is in reserved range " . $detected_doomed_num_range->toString() . ", see the Build Info page - rejecting it", $rampId, true);
            $this->addRejectedDoomEdNum($number, 0, $rampId, $className);
            return false;
        }

        $alreadyReservedDoomEdNumber = $this->getDoomEdNum($number);

        if ($alreadyReservedDoomEdNumber) {
            if (strtolower($className) != strtolower($alreadyReservedDoomEdNumber->className)) {
                Logger::pg("❌ DoomedNum conflict: " . $number . " for " . $className . " already refers to " . $alreadyReservedDoomEdNumber->className . " from map " . $alreadyReservedDoomEdNumber->ownerRampId . ", rejecting it", $rampId, true);
                $this->addRejectedDoomEdNum($number, $alreadyReservedDoomEdNumber->ownerRampId, $rampId, $className);
                return false;
            }
            Logger::pg("⚠️ DoomedNum " . $number . " is already defined for " . $className . " in map " . $alreadyReservedDoomEdNumber->ownerRampId . ". Skipping it, but not considering it an error", $rampId);
            return true;
        }
        $this->addDoomEdNum($number, $rampId, $className);
        Logger::pg("Reserved DoomEdNum " . $number . " = " . $className, $rampId);
        return true;
    }

    public function reserveSpawnNumber($number, $rampId, $className): bool
    {
        $alreadyReservedSpawnNumber = $this->getSpawnNum($number);
        if ($alreadyReservedSpawnNumber) {
            if (strtolower($className) != strtolower($alreadyReservedSpawnNumber->number)) {
                Logger::pg("❌ Spawnnum conflict: " . $number . " for " . $className . " already refers to " . $alreadyReservedSpawnNumber->className . " from map " . $alreadyReservedSpawnNumber->ownerRampId . ", rejecting it", $rampId, true);
                $this->addRejectedSpawnNum($number, $alreadyReservedSpawnNumber->ownerRampId, $rampId, $className);
                return false;
            }
            Logger::pg("⚠️ Spawnnum " . $number . " is already defined for " . $className . " in map " . $alreadyReservedSpawnNumber->ownerRampId . ". Skipping it, but not considering it an error", $rampId);
            return true;
        }
        $this->addSpawnNum($number, $rampId, $className);
        Logger::pg("Reserved Spawnnum " . $number . " = " . $className, $rampId);
        return true;
    }

    public function mapHasRejectedScript($rampId): bool
    {
        return (bool) ($this->rampIdsWithRejectedScripts[$rampId] ?? false);
    }

    public function hasDoomEdNumbers(): bool
    {
        return count($this->doomEdNums) > 0;
    }

    public function hasSpawnNumbers(): bool
    {
        return count($this->spawnNums) > 0;
    }

    public function getDoomEdNumbers(): array
    {
        return $this->doomEdNums;
    }

    public function getSpawnNumbers(): array
    {
        return $this->spawnNums;
    }

    public function jsonSerialize(): array
    {
        return [
            'doomEdNums' => $this->doomEdNums,
            'spawnNums' => $this->spawnNums,
            'rejectedDoomEdNums' => $this->rejectedDoomEdNums,
            'rejectedSpawnNums' => $this->rejectedSpawnNums,
            'mapsWithRejectedScripts' => $this->rampIdsWithRejectedScripts,
            'globalAmbientList' => $this->lumpRegistry->global_ambient_list
        ];
    }
}