const React = require('react');

import PropTypes from "prop-types"

const InputField = (props) => {
    let InputTag

    const {type, name, label, required = false, pattern, defaultValue, options, selectHint, value, min, max} = props

    console.log(value)

    const handleChange = event => {
        console.log("state:", event.target.value)
        props.onChange(event.target.value)
    }

    const handleOptionChange = (value) => {
        props.onChange(value)
    }

    const handleCheckboxChange = (event) => {  
        let result = event.target.checked ? "on" : "off"
        props.onChange(result)
    }

    const selectOptions = () => {
        if (type !== "select") return []
        if (options.length === 0) return []
        
        if(!Array.isArray(options)) {
            const result = []
            Object.entries(options).forEach(entry => {
                const [key, label] = entry;
            
                result.push(<option selected={value == key} key={key} value={key}>{label}</option>)
            });
            return result;
        }

        return options.map((option, index) => {
            return (<option selected={value == option} key={index}>{option}</option>)
        })
    }

    const radioOptions = () => {

        if (type !== "radio") return []
        if (options.length === 0) return []
        return options.map((option, index) => {
            if (typeof option === 'object') {
                return(<div className="radio" key={option.key}>
                    <label htmlFor={option.key}>
                    <input onChange={() => {handleOptionChange(option)}} type="radio" name={`${name}[${option.key}]`} checked={option.name == value} />
                    {option.name}</label>
                </div>)
            }
            
            return (<div key={index}>
                    <label htmlFor={index}>
                    <input onChange={() => {handleOptionChange(option)}} type="radio" name={`${name}[${index}]`} checked={option == value} />
                    {option}</label>
            </div>)
        })
    }


    switch (type) {
        case "select":
            InputTag = (
                <div className="input">
                    <label>{label}</label>
                    <select onChange= {handleChange} name={name} required={required}>
                        { defaultValue && <option value="">{defaultValue}</option>}
                        { !defaultValue && <option value="">{selectHint}</option>}
                        { selectOptions() }
                    </select>
                </div>
            )
            break;
        case "radio":
            InputTag = (
                <div className="radio">
                    <label>{label}</label>
                    <fieldset className="radio">
                        { radioOptions() }
                    </fieldset>
                </div>
            )
            break;
        case "checkbox":
            InputTag = (
                <div className="checkbox">
                    <label>
                    <input onChange={() => {handleCheckboxChange(event)}} type="checkbox" name={name} required={required} />
                    <span>{label}</span>
                    </label>
                </div>
            )
            break;
        case "date":
            InputTag = (
                <div className="input">
                    <label>{label}</label>
                    <input onChange= {event => {handleChange(event)}} type={type} name={name} min={min} max={max} value={value} required={required} pattern={pattern}/>
                </div>
            )
            break;
        case "textarea":
            InputTag = (
                <div className="textarea">
                    <label>{label}</label>
                    <textarea onChange= {handleChange} name={name} value={value} required={required}></textarea>
                </div>
            )
            break;
        default:
            InputTag = (
                <div className="input">
                    <label>{label}</label>
                    <input onChange= {event => {handleChange(event)}} type={type} name={name} required={required} pattern={pattern}/>
                </div>
            )
    }

    return (
        <div>
            { InputTag }
        </div>
        
    )
}

InputField.propTypes = {
    name: PropTypes.string.isRequired,
    type: PropTypes.string.isRequired,
    label: PropTypes.string.isRequired,
    required: PropTypes.bool,
    pattern: PropTypes.string,
    defaultValue: PropTypes.string,
    options: PropTypes.array,
    selectHint: PropTypes.string,
    onChange: PropTypes.func
}

export default InputField
