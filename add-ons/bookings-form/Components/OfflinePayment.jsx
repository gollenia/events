import React, { useEffect, useState } from 'react'
import { __ } from '@wordpress/i18n';
import SVG, { Props as SVGProps } from 'react-inlinesvg';

const OfflinePayment = (props) => {

	const {
        currentGateway: {
            id, title, methods, html
        },
		bookingId
    } = props

	if(!bookingId) return <></>;

	const [paymentInfo, setPaymentInfo] = useState({});

	useEffect(() => {
		fetch(`/wp-admin/admin-ajax.php?action=em_payment_info&booking_id=${bookingId}`).then((response) => response.json()).then((response) => {
			console.log(response)
			if(!response) {
				return;
			}
			setPaymentInfo(response);
			return;
        })		
	}, [])

	return (
		<div>
			<h4>{title}</h4>
			<div className="grid md:grid--columns-3 xl:grid--columns-4 grid--gap-12">
				<div>
					<div className="card card--no-image card--shadow bg-white card--center">
						
						<div className="card__content">
							<div className="card__title">{__("Scan to pay", "em-pro")}</div>
							<SVG className="w-full" src={`/wp-admin/admin-ajax.php?action=em_qr_code&booking_id=${bookingId}`}></SVG>
						</div>
					</div>
				</div>
				<div className='md:grid__column--span-2 xl:grid__column--span-3'>
					<p dangerouslySetInnerHTML={{__html: html}}></p>
					<table className="table--dotted">
						<tr><th className='text-left'>{__('Bank', 'em-pro')}</th><td>{paymentInfo.bank}</td></tr>
						<tr><th className='text-left'>{__('IBAN', 'em-pro')}</th><td>{paymentInfo.iban}</td></tr>
						<tr><th className='text-left'>{__('BIC', 'em-pro')}</th><td>{paymentInfo.bic}</td></tr>
						<tr><th className='text-left'>{__('Beneficial', 'em-pro')}</th><td>{paymentInfo.beneficiary}</td></tr>
						<tr><th className='text-left'>{__('Purpose', 'em-pro')}</th><td>{paymentInfo.purpose}</td></tr>
					</table>
				</div>
			</div>
		</div>
	)
}

export default OfflinePayment