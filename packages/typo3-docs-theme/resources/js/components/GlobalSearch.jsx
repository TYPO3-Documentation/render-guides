import React, { useEffect, useState } from 'react';
import SearchModal from './SearchModal';

const GlobalSearch = () => {
    const [isModalOpen, setIsModalOpen] = useState(false);

    const handleButtonClick = (e) => {
        e.preventDefault();
        setIsModalOpen(true);
    };

    useEffect(() => {
        const form = document.getElementById('global-search-form');
        if (form) {
            form.hidden = true;
        }
    }, []);

    return (
        <>
            {isModalOpen && <SearchModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
            />}
            <button onClick={handleButtonClick} class="btn btn-light"><i class="fa fa-search"></i>&nbsp;<span class="d-none d-md-inline">Search</span></button>
        </>
    );
};

export default GlobalSearch;
