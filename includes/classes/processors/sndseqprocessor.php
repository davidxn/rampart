<?php
class SndSeqProcessor extends LumpProcessor {

    public function process(): bool
    {
        $sndseq_result = $this->lumpRegistry->add_sound_sequences($this->lump->data, $this->rampMap->rampId);
        $this->lump->data = $sndseq_result['cleaned_data'];
        if (!$sndseq_result['success']) {
            Logger::pg(get_error_link('ERR_SOUND_SNDSEQ_CONFLICTS'), $this->rampMap->rampId, true);
        }
        $this->accept();
        return true;
    }
}
