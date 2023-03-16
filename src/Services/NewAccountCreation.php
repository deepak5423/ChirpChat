<?php

namespace App\Services;

/**
 * This class takes the data from user and then process it and return 
 * in the form of array.
 * 
 * @method getAccountDetails()
 *    Takes all the details that user has enter during sign up and returns
 *    in the form of array.
 * 
 * @author Deepak Pandey <deepak.pandey@innoraft.com>
 */
class NewAccountCreation {
    /**
     * Takes all the details that user has enter during sign up and returns
     * in the form of array.
     *
     * @param object $request
     *   Request object handles parameter from query parameter.
     * 
     * @return array
     *   It returns array of data that user has enter during signup.
     */
    public function getAccountDetails($request) {
        $firstName = $request->request->get('fname');
        $lastName = $request->request->get('lname');
        $gender = $request->request->get('gender');
        $image = $request->files->get('image');
        $about = $request->request->get('abotYou');
        $otpUser = $request->request->get('otp');
        $email = $request->request->get('email');
        $pass = $request->request->get('pass');
        $conPass = $request->request->get('confirmPass');

        $arr = [];
        $arr[] = [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'gender' => $gender,
            'image' => $image,
            'about' => $about,
            'otpUser' => $otpUser,
            'email' => $email,
            'pass' => $pass,
            'conPass' => $conPass
        ];
        return $arr[0];
    }
}
?>