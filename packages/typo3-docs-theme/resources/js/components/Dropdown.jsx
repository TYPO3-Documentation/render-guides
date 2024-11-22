import React, { useState, useRef, useEffect } from 'react';

const Dropdown = ({ 
    isActive,
    categories,
    selectedValue,
    onSelect,
    placeholder = 'Select an option'
}) => {
    const [isOpen, setIsOpen] = useState(false);
    const dropdownRef = useRef(null);

    // Close dropdown when clicking outside
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
                setIsOpen(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const handleSelect = (value) => {
        onSelect(value);
        setIsOpen(false);
    };

    // Find the label of the selected value
    const getSelectedLabel = () => {
        for (const category of categories) {
            const item = category.items.find(item => item.value === selectedValue);
            if (item) return item.label;
        }
        return placeholder;
    };

    if (!isActive) return null;

    return (
        <div className="dropdown-container" ref={dropdownRef}>
            <button 
                className="dropdown-toggle btn btn-light"
                onClick={() => setIsOpen(!isOpen)}
                type="button"
                aria-expanded={isOpen}
            >
                <span className="dropdown-selected">{getSelectedLabel()}</span>
                <i className={`fa fa-chevron-${isOpen ? 'up' : 'down'} ms-2`}></i>
            </button>

            {isOpen && (
                <div className="dropdown-menu show">
                    {categories.map((category, index) => (
                        <div key={index} className="dropdown-category">
                            {category.title && (
                                <h6 className="dropdown-header">{category.title}</h6>
                            )}
                            {category.items.map((item) => (
                                <button
                                    key={item.value}
                                    className={`dropdown-item ${selectedValue === item.value ? 'active' : ''}`}
                                    onClick={() => handleSelect(item.value)}
                                    type="button"
                                >
                                    {item.label}
                                </button>
                            ))}
                            {index < categories.length - 1 && (
                                <div className="dropdown-divider"></div>
                            )}
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
};

export default Dropdown;