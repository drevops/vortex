@clamav @p0
Feature: ClamAV Anti-virus

  Ensure that ClamAV is working correctly.

  This test creates a locally hosted fixture virus EICAR test file. This file is
  harmless and is used to test the anti-virus scanner. The file is created in
  the public:// directory and is then uploaded to the site. The test checks that
  the file is rejected and that the anti-virus scanner is working correctly.

  https://en.wikipedia.org/wiki/EICAR_test_file

  Background:
    Given unmanaged file "public://eicar_test.txt" created with content "X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*"
    And unmanaged file "public://test.txt" created with content "some text content"

  @api
  Scenario: Upload EICAR test file to trigger virus detection.
    Given I am logged in as a user with the "administrator" role
    And I go to "media/add/document"
    When I attach the file "public://eicar_test.txt" to "files[field_media_document_0]"
    And press "Upload"
    Then I should see the text "The specified file eicar_test.txt could not be uploaded."
    And I should see the text "A virus has been detected in the file. The file will be deleted."
    And I should not see the text "The anti-virus scanner could not check the file."
    And save screenshot

  @api
  Scenario: Upload test file to ensure that file upload works
    Given I am logged in as a user with the "administrator" role
    And I go to "media/add/document"
    When I attach the file "public://test.txt" to "files[field_media_document_0]"
    And press "Upload"
    Then I should not see the text "The specified file test.txt could not be uploaded."
    And I should not see the text "A virus has been detected in the file. The file will be deleted."
    And I should not see the text "The anti-virus scanner could not check the file."
