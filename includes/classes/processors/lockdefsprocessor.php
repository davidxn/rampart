<?php
class LockDefsProcessor extends LumpProcessor {

    public function process(): bool
    {
        if (str_contains(strtolower($this->lump->data), 'clearlocks')) {
            Logger::pg(get_error_link('ERR_LUMP_LOCKDEFS_CLEARLOCKS'), $this->rampMap->rampId, true);
            return false;
        }

        $lockRegex = "/^lock\s+([0-6]|10[0-1]|129|13[0-4]|229)\s*\{/mi";
        if (preg_match($lockRegex, $this->lump->data)) {
            Logger::pg(get_error_link('ERR_LUMP_LOCKDEFS_CONFLICTS'), $this->rampMap->rampId, true);
            return false;
        }
        $this->accept();
        return true;
    }
}
