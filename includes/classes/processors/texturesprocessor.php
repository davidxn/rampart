<?php
class TexturesProcessor extends LumpProcessor {

    public function process(): bool
    {
        $texture_validation_result = $this->lumpRegistry->validate_textures($this->lump->data, $this->rampMap->rampId);
        $this->lump->data = $texture_validation_result['cleaned_data'];
        if (!$texture_validation_result['success']) {
            Logger::pg(get_error_link('ERR_TEX_CONFLICTS'), $this->rampMap->rampId, true);
        }
        $this->accept();
        return true;
    }
}
