# fontanalib

## PATCHES
### From contrib module directory
- `cd /var/www/html/web/modules/contrib/feeds_para_mapper`
- `git apply /var/www/html/web/modules/custom/fontanalib/patches/feeds_para_mapper-3096198-PHP_notice_undefined_variable.patch`
### From project root
- in directory: `/var/www/html`
- `git apply web/modules/custom/fontanalib/patches/feeds-fix-upload-form-error.patch --directory web/modules/contrib/feeds -p1 --verbose`