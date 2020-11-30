# Enhanced Validation

For all fixes, simply including `ValidationPatch\ValidationPatchServiceProvider` in your `app` config will allow them to take effect automatically.

### Fixes

1. **Dots in field names**: Laravel offers the ability to escape dots in field names so that they're not mistaken for dot-delimiters for nested arrays. However, this did not apply to field references within in rules such as `required_with` or `exclude_unless`. This corrects for that.
2. **Forward slashes in field names**: Due to a parsing errors, field names that had forward slashes, such as URI claims, would cause an error. These are now supported.
3. **`required` only active if parent exists**: If a nested array element carried an incarnation of the `required` rule, it would be executed even if the parent element were optional. With this package, nested requirements only fire if the parent exists.
4. **Custom Rule Message Placeholders**: Allow `Illuminate\Contracts\Validation\Rule::message()` output to be respected when aliased via `Illuminate\Support\Facades\Validator::extend()`. Also allow those messages to contain placeholders such as `"attribute"` or `":input"`.