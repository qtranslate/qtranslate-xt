#!/bin/bash
# Extract translatable strings into the template
xgettext ../admin/*.php ../*.php \
    --from-code=UTF-8 \
    --default-domain=default \
    --language=PHP \
    --no-wrap \
    --keyword=__ \
    --keyword=_e \
    --package-name=qTranslate-X \
    --package-version=3.1 \
    --output qtranslate.pot

for lang in az_AZ bg_BG cs_CZ da_DK de_DE eo es_CA es_ES fr_FR hu_HU id_ID it_IT ja_JP mk_MK ms_MY nl_NL pl_PL pt_BR pt_PT ro_RO ru_RU sr_RS sv_SE tr_TR zh_CN; do
    # Create empty files if they do not exist yet
    touch qtranslate-$lang.po

    # Merge the .po files with the template
    msgmerge --update --lang=$lang qtranslate-$lang.po qtranslate.pot

    # Convert all .po files into .mo
    pocompile qtranslate-$lang.po qtranslate-$lang.mo
done