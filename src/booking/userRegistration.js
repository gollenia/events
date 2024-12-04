import { InputField } from '@contexis/wp-react-form';
//import InputField from '../__experimantalForm/InputField';
import Summary from './summary';

const UserRegistration = ( props ) => {
	const { countTickets, state, dispatch } = props;

	const { error } = state.response;
	const { registration } = state.request;

	const { data, request, response } = state;

	if ( ! data || ! data.registration_fields ) return <></>;

	return (
		<div className="grid xl:grid--columns-2 grid--gap-12">
			<Summary { ...props } />
			<div>
				<form className="form--trap form grid xl:grid--columns-6 grid--gap-8" id="user-registration-form">
					{ data.registration_fields.map( ( field, index ) => (
						<InputField
							{ ...field }
							name={ field.fieldid }
							disabled={ state.state == 'SUBMITTING' }
							key={ index }
							tabIndex={ index + 1 }
							type={ field.type }
							value={ state.request.registration[ field.fieldid ] }
							onChange={ ( event ) => {
								dispatch( {
									type: 'SET_FIELD',
									payload: { form: 'registration', field: field.fieldid, value: event },
								} );
							} }
							locale={ data.l10n.locale }
						/>
					) ) }
					{ data.event?.is_free && data?.l10n?.consent && (
						<InputField
							type="checkbox"
							onChange={ ( event ) => {
								dispatch( {
									type: 'SET_FIELD',
									payload: { form: 'registration', field: 'data_privacy_consent', value: event },
								} );
							} }
							tabIndex={ 98 }
							value={ request.registration.data_privacy_consent }
							settings={ {
								name: 'data_privacy_consent',
								help: data?.l10n?.consent,
								type: 'checkbox',
							} }
						/>
					) }
					<div
						tabIndex={ 99 }
						onFocus={ () => {
							console.log( 'focus' );
							document.getElementById( 'focusButton' ).focus();
						} }
					></div>
					{ data.event.is_free && error != '' && (
						<div class="alert bg-error text-white" dangerouslySetInnerHTML={ { __html: error } }></div>
					) }
				</form>
			</div>
		</div>
	);
};

export default UserRegistration;
