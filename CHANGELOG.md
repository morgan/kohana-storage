# 0.4.0

- Added directory listing support
- Added `Storage_File` for performing actions on single path
- Renamed `Storage` to `Storage_Connection`
- `Storage::factory` returns `Storage_Connection` and is backwards compatible with prior versions
- Updated AWS SDK to 1.5.13
- Updated EMC Atmos SDK to 1.4.1.21
- Updated Rackspace Cloud Files SDK to latest
- All tests pass: OK (34 tests, 69 assertions)

# 0.3.0 - 10/18/2011

- Added mime support for Amazon S3, EMC Atmos and Rackspace Cloud Files
- All tests pass

# 0.2.0 - 09/15/2011

- Updated module to support Kohana 3.2
- All tests pass

# 0.1.0 - 06/25/2011

- Initial release of Storage
- Support for Amazon S3, EMC Atmos, Rackspace Cloud Files, FTP and local file system
- Unit test coverage
