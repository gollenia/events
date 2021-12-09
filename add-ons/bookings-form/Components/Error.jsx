

  import { __ } from '@wordpress/i18n'
  import {qs} from "qs"
  import React, { useEffect, useState } from 'react'
  
  const ErrorFallback = ({error, resetErrorBoundary}) => {

    const [errorSent, setErrorSent] = useState(false);

    const request = {
        error,
        event_id: window.bookingAppData.event_id
    }

    const url = new URL(window.bookingAppData.rest_url)
    url.search = new URLSearchParams(request).toString();


    useEffect(() => {
        fetch(url).then((response) => response.json()).then((response) => {
            console.log(response)
            if(response.result) {
            setErrorSent(true)
            return;
            }

        })
    }, [])
    return (
        <div className="alert bg-error" role="alert">
          <h4>{__("An error occured in our ordering system.", "em-pro")}</h4>
          <p>{__("You may try it later again.", "em-pro")} { errorSent && __("However our admin has been informed and will take care of the problem.", "em-pro") }</p>
          <button class="button button--white" onClick={resetErrorBoundary}>__{"Try again", "em-pro"}</button>
        </div>
      )
  }
  
  export default ErrorFallback
  