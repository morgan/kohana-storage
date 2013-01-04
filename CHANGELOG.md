# 0.6.0 - 01/04/2013

- Upgraded to support Kohana 3.3
- Renamed class files and directories to support PSR-0
- Resolved pass by reference issue (now testing in strict mode)
- All tests pass: "OK (34 tests, 69 assertions)"

# 0.5.0 - 09/21/2012

- Resolved issue #4 (https://github.com/morgan/kohana-storage/issues/4). The issue was that the 
SSL configuration implementation in `Storage_Connection_Ftp::_load` was inverted.
- Resolved issue #5 (https://github.com/morgan/kohana-storage/issues/5). Problem was 
`ErrorException` was thrown due to pass by reference issue in 
`classes/storage/listing/abstract.php#L127`.
- Resolved issue #6 (https://github.com/morgan/kohana-storage/issues/6). This adds configuration 
option `path_style` to S3 driver allowing for older path-style URI access for all requests.
- All tests pass: "OK (34 tests, 69 assertions)"

# 0.4.0 - 09/01/2012

- Added directory listing support
- Added `Storage_File` for performing actions on a single path
- Renamed `Storage` to `Storage_Connection`
- `Storage::factory` returns `Storage_Connection` and is backwards compatible with prior versions
- Updated AWS SDK to 1.5.13
- Updated EMC Atmos SDK to 1.4.1.21
- Updated Rackspace Cloud Files SDK to latest
- All tests pass: "OK (34 tests, 69 assertions)"

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
