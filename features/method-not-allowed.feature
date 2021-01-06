Feature: Testing that we receive a 405 Method not allowed when calling route with invalid method

  Scenario: Call route with invalid method DELETE
    And I send a "DELETE" request to "/api/todos"

    Then the response status code should be 405
